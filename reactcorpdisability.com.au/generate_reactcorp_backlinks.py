#!/usr/bin/env python3
"""
ReactCorp Disability Services — Automated Off-Page SEO Backlink & Indexing Engine
Generates live high-DA backlink articles on Telegraph (telegra.ph) targeting 'NDIS registered provider Sydney NSW'
and related high-intent queries, tracking them in CSV and pinging search engines.
"""

import urllib.request
import urllib.parse
import json
import csv
import time
import random
import sys

CSV_FILE = '/Users/deepeshhamal/Downloads/supportfoundation/reactcorpdisability.com.au/reactcorp_offpage_seo_submissions.csv'

KEYWORDS = [
    ("NDIS registered provider Sydney NSW", "https://reactcorpdisability.com.au/"),
    ("NDIS provider Sydney", "https://reactcorpdisability.com.au/"),
    ("Registered NDIS provider NSW", "https://reactcorpdisability.com.au/"),
    ("ReactCorp Disability Services", "https://reactcorpdisability.com.au/"),
    ("NDIS Support Coordination Group 0132 Sydney", "https://reactcorpdisability.com.au/our-services/"),
    ("Supported Independent Living SIL Accommodation Sydney", "https://reactcorpdisability.com.au/our-services/"),
    ("NDIS Personal Care & In-Home Nursing Sydney", "https://reactcorpdisability.com.au/our-services/"),
    ("NDIS Provider North Kellyville NSW", "https://reactcorpdisability.com.au/"),
    ("24/7 Crisis Support NDIS Sydney", "https://reactcorpdisability.com.au/contact-us/"),
    ("NDIS Price Guide Compliant Provider Sydney", "https://reactcorpdisability.com.au/price/"),
    ("ReactCorp Commitment to SIL Registration 2026", "https://reactcorpdisability.com.au/reactcorp-commitment-to-continue-sil-registration/"),
    ("Complete Participant Guide to Supported Independent Living SIL", "https://reactcorpdisability.com.au/the-complete-participant-guide-to-supported-independent-living-sil/"),
    ("NDIS Knowledge Hub & Blog Sydney", "https://reactcorpdisability.com.au/blog/"),
    ("Online NDIS Intake & Referral Form", "https://zfrmz.com/sIh6uDqI2c9PaujmOoTR")
]

TITLE_TEMPLATES = [
    "Comprehensive Guide: {keyword} — ReactCorp Disability Services 2026",
    "{keyword} & Participant Care Rights Across Sydney & NSW",
    "Selecting an Official {keyword} — Quality & Safeguards Overview",
    "Expert Advice on {keyword} in North Kellyville & Greater Sydney",
    "Official Resource: {keyword} — ReactCorp Disability Services Australia",
    "How to Choose an {keyword} Delivering 24/7 Crisis Response",
    "Understanding {keyword}: Plan Optimization & Capacity Building",
    "Best Practices in {keyword} Under the NDIS Quality Standards"
]

INSIGHT_PARAGRAPHS = [
    "Under the National Disability Insurance Scheme (NDIS), choosing a registered provider gives participants guaranteed protection under the NDIS Quality and Safeguards Commission. ReactCorp Disability Services (Provider Registration #4050064716) operates across New South Wales, Victoria, Australian Capital Territory, South Australia, and Tasmania.",
    "ReactCorp's multi-disciplinary team supports Agency-Managed, Plan-Managed, and Self-Managed participants. With our head office located at 20 Barabati Road, North Kellyville NSW 2155, our clinical coordinators deliver rapid emergency intake, crisis accommodation, and specialized coordination.",
    "From 1 July 2026, mandatory NDIS registration for Supported Independent Living (SIL) requires strict compliance across Supported Decision-Making, Zero Harm Safeguarding, Practice Governance, and separate tenancy agreements. ReactCorp is fully prepared and compliant with these standards.",
    "Navigating NDIS pricing shouldn't be stressful. ReactCorp adheres 100% to NDIA pricing limits with transparent, line-by-line accounting, ensuring zero out-of-pocket administration surprises for participants and their plan managers."
]

def get_telegraph_token():
    acc_url = 'https://api.telegra.ph/createAccount?short_name=ReactCorp&author_name=' + urllib.parse.quote('ReactCorp Disability Services')
    res = json.loads(urllib.request.urlopen(acc_url, timeout=15).read().decode('utf-8'))
    return res['result']['access_token']

def submit_indexnow():
    """Ping IndexNow API with ReactCorp site URLs for instant crawler indexing."""
    site_urls = [
        "https://reactcorpdisability.com.au/",
        "https://reactcorpdisability.com.au/our-services/",
        "https://reactcorpdisability.com.au/price/",
        "https://reactcorpdisability.com.au/contact-us/",
        "https://reactcorpdisability.com.au/blog/",
        "https://reactcorpdisability.com.au/reactcorp-commitment-to-continue-sil-registration/",
        "https://reactcorpdisability.com.au/the-complete-participant-guide-to-supported-independent-living-sil/"
    ]
    try:
        payload = json.dumps({
            "host": "reactcorpdisability.com.au",
            "key": "d8975877c38541e285dcbafef32709e9",
            "keyLocation": "https://reactcorpdisability.com.au/d8975877c38541e285dcbafef32709e9.txt",
            "urlList": site_urls
        }).encode()
        req = urllib.request.Request(
            "https://api.indexnow.org/IndexNow",
            data=payload,
            headers={"Content-Type": "application/json; charset=utf-8"}
        )
        with urllib.request.urlopen(req, timeout=10) as resp:
            return resp.status
    except Exception as e:
        return str(e)

def publish_backlink_batch(count=50):
    token = get_telegraph_token()
    published_count = 0
    generated_urls = []
    
    print(f"[*] Starting high-DA backlink deployment batch ({count} articles)...")
    
    for i in range(count):
        keyword, target_url = random.choice(KEYWORDS)
        title_tpl = random.choice(TITLE_TEMPLATES)
        title = title_tpl.format(keyword=keyword) + f" (Ref: #{random.randint(1000, 9999)})"
        insight = random.choice(INSIGHT_PARAGRAPHS)
        
        content_nodes = [
            {'tag': 'h3', 'children': ['Authoritative Guide & Participant Resource']},
            {'tag': 'p', 'children': [
                insight,
                ' If you are looking for an official ',
                {'tag': 'a', 'attrs': {'href': target_url}, 'children': [keyword]},
                ', ReactCorp Disability Services delivers compassionate, reliable, and person-centred supports tailored to your goals.'
            ]},
            {'tag': 'h4', 'children': ['Core Service Highlights & Registration Groups']},
            {'tag': 'ul', 'children': [
                {'tag': 'li', 'children': ['Group 0132: Support Coordination (Level 1, 2 & Level 3 Specialist Coordination)']},
                {'tag': 'li', 'children': ['Group 0101: Supported Independent Living (SIL), STA Respite & Crisis Housing']},
                {'tag': 'li', 'children': ['Group 0107: High Intensity Daily Personal Care & In-Home Nursing']},
                {'tag': 'li', 'children': ['Group 0116: Social, Community Access & Innovative Participation']}
            ]},
            {'tag': 'p', 'children': [
                '📍 Head Office: 20 Barabati Road, North Kellyville NSW 2155, Sydney, Australia\n',
                '📞 24/7 Emergency Line: ',
                {'tag': 'a', 'attrs': {'href': 'tel:0422069482'}, 'children': ['0422 069 482']},
                '\n🌐 Official Online Intake: ',
                {'tag': 'a', 'attrs': {'href': 'https://zfrmz.com/sIh6uDqI2c9PaujmOoTR'}, 'children': ['Submit NDIS Participant Referral Online']}
            ]}
        ]
        
        data = {
            'access_token': token,
            'title': title,
            'author_name': 'ReactCorp Disability Services',
            'author_url': 'https://reactcorpdisability.com.au/',
            'content': json.dumps(content_nodes),
            'return_content': True
        }
        
        try:
            encoded_data = urllib.parse.urlencode(data).encode('utf-8')
            page_req = urllib.request.Request('https://api.telegra.ph/createPage', data=encoded_data)
            page_res = json.loads(urllib.request.urlopen(page_req, timeout=15).read().decode('utf-8'))
            page_url = page_res['result']['url']
            
            with open(CSV_FILE, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow(['telegra.ph', 'Live Published Article', 'LIVE & INDEXED', target_url, keyword, page_url])
            
            published_count += 1
            generated_urls.append(page_url)
            print(f"[{published_count}/{count}] Published: {keyword} -> {page_url}")
            time.sleep(0.3)
        except Exception as e:
            print(f"Error publishing: {e}")
            time.sleep(1)

    print(f"\n[✓] Successfully published {published_count} live high-DA off-page backlink articles for ReactCorp!")
    
    # Ping IndexNow for core site URLs
    print("[*] Submitting ReactCorp URLs to IndexNow search engine ping network...")
    ping_status = submit_indexnow()
    print(f"[✓] IndexNow ping completed with status: {ping_status}")

if __name__ == '__main__':
    batch_size = int(sys.argv[1]) if len(sys.argv) > 1 else 25
    publish_backlink_batch(batch_size)
