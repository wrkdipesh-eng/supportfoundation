#!/usr/bin/env python3
"""
ReactCorp Disability Services — Automated Off-Page SEO Backlink Generator Engine
Generates live high-DA backlink articles on Telegraph (telegra.ph) and tracks them in CSV.
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
    ("ReactCorp Disability Services", "https://reactcorpdisability.com.au/"),
    ("Registered NDIS Service Provider Australia", "https://reactcorpdisability.com.au/blog/#pillar-2"),
    ("NDIS Provider Sydney NSW", "https://reactcorpdisability.com.au/blog/#pillar-3"),
    ("NDIS Support Coordination Group 0132", "https://reactcorpdisability.com.au/blog/#pillar-4"),
    ("Supported Independent Living SIL Accommodation", "https://reactcorpdisability.com.au/blog/#pillar-5"),
    ("NDIS Personal Care & High Intensity Nursing", "https://reactcorpdisability.com.au/blog/#pillar-6"),
    ("NDIS Community Participation", "https://reactcorpdisability.com.au/blog/#pillar-7"),
    ("24/7 Crisis Support NDIS Respite Care", "https://reactcorpdisability.com.au/blog/#pillar-8"),
    ("NDIS Provider Registration Number 4050064716", "https://reactcorpdisability.com.au/blog/#pillar-9"),
    ("Online NDIS Referral Form", "https://zfrmz.com/sIh6uDqI2c9PaujmOoTR")
]

TITLE_TEMPLATES = [
    "Guide to {keyword} — ReactCorp Disability Services 2026",
    "{keyword} & Participant Care Rights in Sydney & Australia",
    "Understanding {keyword} — Quality Disability Supports",
    "Best Practices for {keyword} in NSW, VIC, ACT, SA & TAS",
    "Official Resource: {keyword} — ReactCorp Disability Services"
]

def get_telegraph_token():
    acc_url = 'https://api.telegra.ph/createAccount?short_name=ReactCorp&author_name=' + urllib.parse.quote('ReactCorp Disability Services')
    res = json.loads(urllib.request.urlopen(acc_url).read().decode('utf-8'))
    return res['result']['access_token']

def publish_backlink_batch(count=100):
    token = get_telegraph_token()
    published_count = 0
    
    for i in range(count):
        keyword, target_url = random.choice(KEYWORDS)
        title_tpl = random.choice(TITLE_TEMPLATES)
        title = title_tpl.format(keyword=keyword) + f" #{random.randint(100, 9999)}"
        
        content_nodes = [
            {'tag': 'p', 'children': [
                'ReactCorp Disability Services operates as a Registered NDIS Provider (NDIS Registration #4050064716). Learn more about ',
                {'tag': 'a', 'attrs': {'href': target_url}, 'children': [keyword]},
                ' delivering 24/7 crisis support, support coordination, SIL accommodation, and personal care across NSW, VIC, ACT, SA, and TAS.'
            ]},
            {'tag': 'p', 'children': [
                'Submit direct participant referrals online via our ',
                {'tag': 'a', 'attrs': {'href': 'https://zfrmz.com/sIh6uDqI2c9PaujmOoTR'}, 'children': ['Online NDIS Intake Form']},
                ' or call our 24/7 support line at 0422 069 482.'
            ]}
        ]
        
        data = {
            'access_token': token,
            'title': title,
            'author_name': 'ReactCorp Disability Services',
            'content': json.dumps(content_nodes),
            'return_content': True
        }
        
        try:
            encoded_data = urllib.parse.urlencode(data).encode('utf-8')
            page_req = urllib.request.Request('https://api.telegra.ph/createPage', data=encoded_data)
            page_res = json.loads(urllib.request.urlopen(page_req).read().decode('utf-8'))
            page_url = page_res['result']['url']
            
            with open(CSV_FILE, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow(['telegra.ph', 'Live Published Article', 'LIVE & INDEXED', target_url, keyword, page_url])
            
            published_count += 1
            print(f"[{published_count}/{count}] Published: {title} -> {page_url}")
            time.sleep(0.2) # Avoid rate limits
        except Exception as e:
            print(f"Error publishing: {e}")
            time.sleep(1)

    print(f"Successfully generated and published {published_count} live backlink articles for ReactCorp.")

if __name__ == '__main__':
    batch_size = int(sys.argv[1]) if len(sys.argv) > 1 else 100
    publish_backlink_batch(batch_size)
