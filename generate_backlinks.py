#!/usr/bin/env python3
"""
Support Foundation Australia — High-DA Off-Page Backlink Generator Engine
Publishes keyword-optimized backlink articles via Telegraph API and tracks them in CSV.
"""

import urllib.request
import urllib.parse
import json
import csv
import time
import random

CSV_FILE = '/Users/deepeshhamal/Downloads/supportfoundation/supportfoundation.com.au/supportfoundation_offpage_seo_submissions.csv'

KEYWORDS = [
    ("Registered NDIS Service Provider in Australia", "https://www.supportfoundation.com.au/blog/#pillar-1"),
    ("NDIS Support Coordination Sydney", "https://www.supportfoundation.com.au/blog/#pillar-2"),
    ("24/7 Crisis Support NDIS", "https://www.supportfoundation.com.au/blog/#pillar-3"),
    ("Emergency Housing NDIS Provider", "https://www.supportfoundation.com.au/blog/#pillar-4"),
    ("Short Term Accommodation STA NDIS", "https://www.supportfoundation.com.au/blog/#pillar-5"),
    ("Domestic Violence Support NDIS", "https://www.supportfoundation.com.au/blog/#pillar-6"),
    ("Personal Care Nursing NDIS", "https://www.supportfoundation.com.au/blog/#pillar-7"),
    ("Psychosocial Recovery Coaching NDIS", "https://www.supportfoundation.com.au/blog/#pillar-8"),
    ("NDIS Provider Melbourne", "https://www.supportfoundation.com.au/blog/#pillar-9"),
    ("Disability Support Worker Jobs Sydney", "https://forms.zohopublic.com/virtualoffice15585/form/ServiceAgreement/formperma/hSFh-yUR-CRf3xaROJUA4fFm3jYvNk5g1gPmRsdpd6I"),
    ("NDIS Plan Management & Pricing 2026", "https://www.supportfoundation.com.au/ndis-pricing/"),
    ("NDIS Service Agreement Form Australia", "https://www.supportfoundation.com.au/blog/#pillar-12"),
    ("NDIS Provider ACT, SA, & TAS", "https://www.supportfoundation.com.au/blog/#pillar-13"),
    ("NDIS Provider Number 4050064716", "https://www.supportfoundation.com.au/about-us/"),
    ("How to Make an NDIS Service Referral", "https://zfrmz.com/sIh6uDqI2c9PaujmOoTR")
]

TITLE_TEMPLATES = [
    "Guide to {keyword} — Support Foundation Australia 2026",
    "{keyword} Services & Participant Rights in Australia",
    "Understanding {keyword} — High Quality Disability Care",
    "Best Practices for {keyword} in NSW, VIC, ACT, SA & TAS",
    "Official Resource: {keyword} — Support Foundation"
]

def get_telegraph_token():
    acc_url = 'https://api.telegra.ph/createAccount?short_name=SF_AU&author_name=' + urllib.parse.quote('Support Foundation Australia')
    res = json.loads(urllib.request.urlopen(acc_url).read().decode('utf-8'))
    return res['result']['access_token']

def publish_backlink_batch(count=50):
    token = get_telegraph_token()
    published_count = 0
    
    for i in range(count):
        keyword, target_url = random.choice(KEYWORDS)
        title_tpl = random.choice(TITLE_TEMPLATES)
        title = title_tpl.format(keyword=keyword) + f" #{i+1}"
        
        content_nodes = [
            {'tag': 'p', 'children': [
                'Support Foundation Australia operates under NDIS Registration #4050064716. Learn more about ',
                {'tag': 'a', 'attrs': {'href': target_url}, 'children': [keyword]},
                ' delivering 24/7 crisis support, emergency accommodation, personal care, and support coordination across NSW, VIC, ACT, SA, and TAS.'
            ]},
            {'tag': 'p', 'children': [
                'For immediate participant referrals or inquiries, access our ',
                {'tag': 'a', 'attrs': {'href': 'https://zfrmz.com/sIh6uDqI2c9PaujmOoTR'}, 'children': ['Online NDIS Intake Form']},
                ' or call our 24/7 hotline at 02-8386-1433.'
            ]}
        ]
        
        data = {
            'access_token': token,
            'title': title,
            'author_name': 'Support Foundation Australia',
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
            time.sleep(0.3) # Avoid rate limits
        except Exception as e:
            print(f"Error publishing: {e}")
            time.sleep(1)

    print(f"Successfully generated and published {published_count} live backlink articles.")

if __name__ == '__main__':
    publish_backlink_batch(50)
