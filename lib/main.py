"""Example of an application(currency convertor) that uses Talk Bot APIs."""

import requests
import re
import json
from pymisp import PyMISP, MISPEvent, MISPAttribute
from contextlib import asynccontextmanager
from typing import Annotated

import httpx
from fastapi import BackgroundTasks, Depends, FastAPI, Response

from nc_py_api import NextcloudApp, talk_bot
from nc_py_api.ex_app import AppAPIAuthMiddleware, atalk_bot_msg, run_app, set_handlers


# The same stuff as for usual External Applications
@asynccontextmanager
async def lifespan(app: FastAPI):
    set_handlers(app, enabled_handler)
    yield


APP = FastAPI(lifespan=lifespan)
APP.add_middleware(AppAPIAuthMiddleware)


# We define bot globally, so if no `multiprocessing` module is used, it can be reused by calls.
# All stuff in it works only with local variables, so in the case of multithreading, there should not be problems.
MISP_BOT = talk_bot.MISPBot("/misp_bot", "MISP IoC Importer", "Usage: `Send any IPs to import to the connected MISP IoC Sharing Plattform`")

def misp_talk_bot_extract_ips(payload):
    # Regular expression pattern for matching IPv4 addresses
    ipv4_pattern = r'\b(?:\d{1,3}\.){3}\d{1,3}\b'
    
    # Find all matches in the given payload
    potential_ips = re.findall(ipv4_pattern, payload)
    
    # Function to classify IPs
    def classify_ip(ip):
        octets = list(map(int, ip.split('.')))
        if (octets[0] == 10 or
            (octets[0] == 172 and 16 <= octets[1] <= 31) or
            (octets[0] == 192 and octets[1] == 168) or
            octets[0] == 127 or
            (octets[0] == 169 and octets[1] == 254)):
            return "private"
        if all(0 <= octet <= 255 for octet in octets):
            return "public"
        return "invalid"
    
    public_ips = [ip for ip in potential_ips if classify_ip(ip) == "public"]
    private_ips = [ip for ip in potential_ips if classify_ip(ip) == "private"]
    
    return {"public_ips": public_ips, "private_ips": private_ips}

def misp_talk_bot_submit_iocs(ips, misp_url, misp_api_key):
    """
    Creates a new MISP event and submits a list of IPs as attributes using PyMISP.

    :param ips: List of IP addresses to submit as IOCs
    :param misp_url: URL of the MISP instance (e.g., https://misp.example.com)
    :param misp_api_key: API key for authentication
    :param event_info: Description of the event
    :param threat_level_id: Threat level (1=High, 2=Medium, 3=Low, 4=Undefined)
    :return: The created MISP event details
    """
    
    # Connect to MISP
    misp = PyMISP(misp_url, misp_api_key, ssl=True)

    # Step 1: Create a new event
    event = MISPEvent()
    event.info = "Auto-generated event by the NextCloud MISP Bot to attach new submitted Public IPv4 IoCs"
    event.threat_level_id = 3
    event.distribution = 1  # Community visibility
    event.analysis = 2  # Completed analysis
    event.published = False

    event = misp.add_event(event)
    if not event or "Event" not in event:
        return {"error": "Failed to create event", "details": event}

    event_id = event["Event"]["id"]
    print(f"Created new event with ID: {event_id}")

    # Step 2: Add IP addresses to the event
    attributes = []
    for ip in ips:
        attr = MISPAttribute()
        attr.type = "ip-src"
        attr.category = "Network activity"
        attr.value = ip
        attr.to_ids = True
        attr.comment = "Submitted by misp_talk_bot"
        attributes.append(attr)

    # Add attributes to event
    misp.update_event(event_id, attributes)

    return misp.get_event(event_id)

def misp_talk_bot_process_request(message: talk_bot.TalkBotMessage):
    try:
        # Ignore `system` messages
        if message.object_name != "message":
            return
        # We use a wildcard search to only respond to messages sent to us.
        r = re.search(
            r"@misp\s(.*)", message.object_content["message"], re.IGNORECASE
        )
        if r is None:
            return
        misp_ioc_payload = r.group(1)
        ip_extraction = misp_talk_bot_extract_ips(misp_ioc_payload)

        reply_message = None
        if len(ip_extraction['private_ips']) != 0:
            reply_message = f"Failure: There were private IPv4 addresses submitted for submission!"
        elif len(ip_extraction['public_ips']) == 0:
            reply_message = f"Failure: There were no valid IPv4 addresses submitted for submission:\n- {ip_extraction['public_ips'].join('\n- ')}"
        else:
            #misp_event = misp_talk_bot_submit_iocs(ip_extraction['public_ips'])
            reply_message = f"Success: The following IPv4 addresses were submitted to the connect MISP IoC Sharing Plattform:\n- {ip_extraction['public_ips'].join('\n- ')}"

        # Send reply to chat
        MISP_BOT.send_message(reply_message, message)
    except Exception as e:
        # In production, it is better to write to log, than in the chat ;)
        MISP_BOT.send_message(f"Exception: {e}", message)


@APP.post("/misp_talk_bot")
async def misp_talk_bot(
    message: Annotated[talk_bot.TalkBotMessage, Depends(atalk_bot_msg)],
    background_tasks: BackgroundTasks,
):
    # As during converting, we do not process converting locally, we perform this in background, in the background task.
    background_tasks.add_task(misp_talk_bot_process_request, message)
    # Return Response immediately for Nextcloud, that we are ok.
    return Response()


def enabled_handler(enabled: bool, nc: NextcloudApp) -> str:
    print(f"enabled={enabled}")
    try:
        # `enabled_handler` will install or uninstall bot on the server, depending on ``enabled`` parameter.
        MISP_BOT.enabled_handler(enabled, nc)
    except Exception as e:
        return str(e)
    return ""


if __name__ == "__main__":
    run_app("main:APP", log_level="trace")
