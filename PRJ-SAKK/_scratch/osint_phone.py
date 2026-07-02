#!/usr/bin/env python3
"""
SOFI OSINT Phone Recon — Advanced Phone Number Investigation Suite
Usage: python3 osint_phone.py +966542885769
"""

import asyncio, json, re, sys, time
from datetime import datetime
from urllib.parse import quote_plus

import aiohttp
from bs4 import BeautifulSoup
from rich.console import Console, Group
from rich.panel import Panel
from rich.table import Table
from rich.text import Text
from rich import box
from rich.columns import Columns
from rich.markdown import Markdown
from rich.progress import Progress, SpinnerColumn, TextColumn

console = Console()

PHONE = sys.argv[1] if len(sys.argv) > 1 else "+966542885769"
# Normalize
RAW = PHONE.replace(" ", "").replace("-", "").replace("(", "").replace(")", "")
if RAW.startswith("+"):
    COUNTRY_CODE = RAW[1:4]  # e.g. 966
    LOCAL = RAW[4:]          # e.g. 542885769
elif RAW.startswith("00"):
    COUNTRY_CODE = RAW[2:5]
    LOCAL = RAW[5:]
else:
    COUNTRY_CODE = "966"
    LOCAL = RAW

FMT_SAUDI = f"05{LOCAL[1:]}" if len(LOCAL) == 9 else f"0{LOCAL}"  # 0542885769
FMT_INTL = f"+{COUNTRY_CODE}{LOCAL}"  # +966542885769
FMT_DOTS = f"+{COUNTRY_CODE} {LOCAL[:2]} {LOCAL[2:6]} {LOCAL[6:]}"  # formatted

# --- Carrier Detection ---
CARRIER_MAP = {
    "50": ("STC (الاتصالات السعودية)", "سوا"),
    "53": ("STC (الاتصالات السعودية)", "سوا"),
    "55": ("STC (الاتصالات السعودية)", "سوا"),
    "58": ("STC (الاتصالات السعودية)", "سوا"),
    "54": ("Mobily (موبايلي)", "موبايلي"),
    "56": ("Mobily (موبايلي)", "موبايلي"),
    "59": ("Mobily (موبايلي)", "موبايلي"),
    "57": ("Zain (زين)", "زين"),
    "58": ("Zain (زين)", "زين"),
}

PREFIX = LOCAL[:2] if LOCAL else ""
carrier_info = CARRIER_MAP.get(PREFIX, ("غير معروف", "غير معروف"))

# ============================================================
# OSINT CHECKS
# ============================================================

async def check_whatsapp(session):
    """Check if number registered on WhatsApp via web preview"""
    url = f"https://wa.me/{COUNTRY_CODE}{LOCAL}"
    try:
        async with session.get(url, timeout=10) as resp:
            html = await resp.text()
            if "send?phone" in html or "Send Message" in html or "ال WhatsApp" in html or "chat" in html.lower():
                return {"status": "✅ موجود", "url": url, "method": "WhatsApp Web"}
            return {"status": "❌ غير موجود", "url": url, "method": "WhatsApp Web"}
    except:
        return {"status": "⚠️ فشل الاتصال", "url": url, "method": "WhatsApp Web"}


async def check_telegram(session):
    """Check Telegram profile"""
    url = f"https://t.me/{COUNTRY_CODE}{LOCAL}"
    try:
        async with session.get(url, timeout=10) as resp:
            if resp.status == 200:
                html = await resp.text()
                if "If you have" not in html and "Telegram" in html:
                    return {"status": "✅ موجود", "url": url}
            return {"status": "❌ غير موجود", "url": url}
    except:
        return {"status": "⚠️ فشل الاتصال", "url": url}


async def check_ksanumbers(session):
    """Check KSA Numbers directory"""
    url = "https://ksanumbers.com/"
    data = {"number": FMT_SAUDI}
    try:
        async with session.post(url, data=data, timeout=15) as resp:
            text = await resp.text()
            soup = BeautifulSoup(text, 'lxml')
            # Look for results
            if "name" in text.lower() or "صاحب" in text or "اسم" in text:
                names = re.findall(r'[\u0600-\u06FF]{3,}[\s\u0600-\u06FF]+', text)
                return {"status": "✅ بيانات موجودة", "raw": text[:500], "names": names[:5]}
            return {"status": "ℹ️ لا توجد نتيجة واضحة", "raw": text[:200]}
    except Exception as e:
        return {"status": f"⚠️ خطأ: {str(e)[:50]}"}


async def check_numberozo(session):
    """Check Numberozo directory"""
    url = f"https://numberozo.com/?s={COUNTRY_CODE}{LOCAL}"
    try:
        async with session.get(url, timeout=15, allow_redirects=True) as resp:
            text = await resp.text()
            if "name" in text.lower() or "صاحب" in text or FMT_SAUDI in text:
                return {"status": "✅ بيانات موجودة", "raw": text[:300]}
            return {"status": "ℹ️ لا توجد نتيجة", "raw": text[:200]}
    except Exception as e:
        return {"status": f"⚠️ فشل: {str(e)[:50]}"}


async def check_numberbooksaudia(session):
    """Check NumberBook Saudi"""
    url = f"https://numberbooksaudia.com/search?q={FMT_SAUDI}"
    try:
        async with session.get(url, timeout=15, allow_redirects=True) as resp:
            text = await resp.text()
            if "name" in text.lower() or "صاحب" in text or FMT_SAUDI in text:
                return {"status": "✅", "raw": text[:300]}
            return {"status": "ℹ️", "raw": text[:200]}
    except:
        return {"status": "⚠️ فشل"}


async def check_truecaller_public(session):
    """Try Truecaller public search"""
    # Truecaller blocks automated access, but we try
    url = f"https://www.truecaller.com/search/sa/{FMT_SAUDI}"
    headers = {
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "ar,en;q=0.9",
    }
    try:
        async with session.get(url, headers=headers, timeout=15, allow_redirects=True) as resp:
            text = await resp.text()
            if resp.status == 200 and "name" in text.lower() and "search" not in text.lower():
                names = re.findall(r'>([\u0600-\u06FF\s]{3,})<', text)
                return {"status": "✅ تم العثور", "raw": text[:500], "names": names[:5]}
            return {"status": "ℹ️ محجوب (يتطلب VPN)", "status_code": resp.status}
    except Exception as e:
        return {"status": f"⚠️ فشل: {str(e)[:50]}"}


async def search_google(session):
    """Search Google for the number"""
    queries = [
        f'"+966542885769"',
        f'"0542885769" سعودي',
        f'"966542885769"',
    ]
    results = []
    for q in queries:
        url = f"https://www.google.com/search?q={quote_plus(q)}&hl=ar"
        headers = {"User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36"}
        try:
            async with session.get(url, headers=headers, timeout=10) as resp:
                text = await resp.text()
                soup = BeautifulSoup(text, 'lxml')
                # Extract snippets
                snippets = []
                for div in soup.select('div[class*="BNeawe"], div.g, span.st'):
                    t = div.get_text(strip=True)
                    if t and len(t) > 20 and len(t) < 300:
                        snippets.append(t)
                if snippets:
                    results.append({"query": q, "snippets": snippets[:3]})
        except:
            pass
    return results if results else [{"query": "Google", "snippets": ["لا نتائج (محظور أو لا يوجد)"]}]


async def search_bing(session):
    """Search Bing for the number"""
    url = f"https://www.bing.com/search?q={quote_plus(FMT_INTL)}"
    headers = {"User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36"}
    try:
        async with session.get(url, headers=headers, timeout=10) as resp:
            text = await resp.text()
            soup = BeautifulSoup(text, 'lxml')
            results = []
            for cite in soup.select('li.b_algo'):
                t = cite.get_text(strip=True)[:200] if cite else ""
                if t:
                    results.append(t)
            if results:
                return results[:5]
            return ["لا نتائج"]
    except:
        return ["⚠️ فشل البحث في Bing"]


async def check_pastebin(session):
    """Search paste sites for the number"""
    urls = [
        f"https://psbdmp.ws/api/search/{FMT_SAUDI}",
        f"https://psbdmp.ws/api/search/{FMT_INTL}",
    ]
    results = []
    for url in urls:
        try:
            async with session.get(url, timeout=10) as resp:
                if resp.status == 200:
                    data = await resp.json()
                    if isinstance(data, list) and len(data) > 0:
                        results.append({"source": url, "count": len(data)})
        except:
            pass
    return results if results else [{"source": "pastebin/psbdmp", "count": 0, "note": "لا توجد تسريبات"}]


async def check_social_media(session):
    """Check social media platforms for the number"""
    results = {}
    
    # Instagram
    try:
        url = f"https://www.instagram.com/web/search/topsearch/?query={FMT_SAUDI}"
        async with session.get(url, timeout=10) as resp:
            if resp.status == 200:
                data = await resp.json()
                users = data.get("users", [])
                results["Instagram"] = f"{len(users)} حساب" if users else "لا يوجد"
    except:
        results["Instagram"] = "⚠️ فشل"
    
    # Twitter/X
    try:
        url = f"https://x.com/search?q={quote_plus(FMT_INTL)}&src=typed_query"
        async with session.get(url, timeout=10) as resp:
            text = await resp.text()
            if "tweet" in text or "status" in text:
                results["X/Twitter"] = "✅ نتائج محتملة"
            else:
                results["X/Twitter"] = "لا يوجد"
    except:
        results["X/Twitter"] = "⚠️ فشل"
    
    # TikTok
    try:
        url = f"https://www.tiktok.com/search?q={quote_plus(FMT_SAUDI)}"
        async with session.get(url, timeout=10) as resp:
            text = await resp.text()
            if FMT_SAUDI in text or LOCAL in text:
                results["TikTok"] = "✅ إشارة محتملة"
            else:
                results["TikTok"] = "لا يوجد"
    except:
        results["TikTok"] = "⚠️ فشل"
    
    # Snapchat
    try:
        url = f"https://www.snapchat.com/add/{LOCAL}"
        async with session.get(url, timeout=10) as resp:
            if resp.status == 200:
                results["Snapchat"] = "✅ يوزر محتمل"
            else:
                results["Snapchat"] = "❌ غير موجود"
    except:
        results["Snapchat"] = "⚠️ فشل"
    
    return results


async def check_leakcheck(session):
    """Check leak-check.net for breaches (public API)"""
    url = f"https://leak-check.net/api/v1/phone/{COUNTRY_CODE}{LOCAL}"
    try:
        async with session.get(url, timeout=10) as resp:
            if resp.status == 200:
                data = await resp.json()
                return data
            return {"status": "لا توجد بيانات تسريب", "code": resp.status}
    except:
        return {"status": "⚠️ فشل الاتصال بقاعدة التسريبات"}


async def check_facebook(session):
    """Try to find Facebook account by phone"""
    url = f"https://www.facebook.com/search/top?q={quote_plus(FMT_SAUDI)}"
    headers = {"User-Agent": "Mozilla/5.0"}
    try:
        async with session.get(url, headers=headers, timeout=10) as resp:
            text = await resp.text()
            if "profile" in text and ("name" in text or "facebook.com/" in text):
                return "✅ حساب محتمل (يتطلب تسجيل دخول)"
            return "ℹ️ لا يمكن التأكد (يتطلب تسجيل دخول)"
    except:
        return "⚠️ فشل"


# ============================================================
# MAIN
# ============================================================

async def main():
    console.clear()
    
    # Header
    console.print(Panel.fit(
        f"[bold cyan]🕵️ SOFI OSINT Phone Reconnaissance[/bold cyan]\n"
        f"[white]بحث استخباراتي متقدم للرقم {FMT_INTL}[/white]\n"
        f"[dim]{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}[/dim]",
        border_style="cyan"
    ))
    
    connector = aiohttp.TCPConnector(limit=10)
    timeout = aiohttp.ClientTimeout(total=30)
    
    async with aiohttp.ClientSession(connector=connector, timeout=timeout) as session:
        
        # ---- PHASE 1: Basic Analysis ----
        console.rule("[bold yellow]📋 المرحلة 1: تحليل الرقم الأساسي")
        
        info_table = Table(show_header=False, box=box.ROUNDED, border_style="blue")
        info_table.add_column("المعلومة", style="cyan", width=20)
        info_table.add_column("القيمة", style="white")
        info_table.add_row("📞 الرقم الدولي", FMT_INTL)
        info_table.add_row("🇸🇦 الرقم المحلي", FMT_SAUDI)
        info_table.add_row("🌍 الدولة", f"المملكة العربية السعودية (+{COUNTRY_CODE})")
        info_table.add_row("📶 الشبكة", f"{carrier_info[0]} ({carrier_info[1]})")
        info_table.add_row("📱 النوع", "جوال (Mobile)")
        info_table.add_row("🔢 طول الرقم", f"{len(LOCAL)} أرقام")
        info_table.add_row("🔄 قابلية نقل", "نعم — ممكن منقول بين STC/موبايلي/زين")
        info_table.add_row("📍 التغطية", "جميع مناطق السعودية")
        console.print(info_table)
        
        # ---- PHASE 2: Parallel OSINT checks ----
        console.rule("[bold yellow]🔬 المرحلة 2: البحث المتوازي في 15+ مصدر")
        
        with Progress(
            SpinnerColumn(),
            TextColumn("[progress.description]{task.description}"),
            console=console,
        ) as progress:
            
            task = progress.add_task("[cyan]جاري البحث...", total=12)
            
            # Fire all checks in parallel
            results = await asyncio.gather(
                check_whatsapp(session),
                check_telegram(session),
                check_ksanumbers(session),
                check_numberozo(session),
                check_numberbooksaudia(session),
                check_truecaller_public(session),
                search_google(session),
                search_bing(session),
                check_pastebin(session),
                check_social_media(session),
                check_leakcheck(session),
                check_facebook(session),
            )
            
            progress.update(task, completed=12)
        
        (
            whatsapp, telegram, ksanums, numberozo_result,
            numbook, truecaller, google_res, bing_res,
            pastebin_res, social_res, leakcheck_res, facebook_res
        ) = results
        
        # ---- PHASE 3: Messaging Apps ----
        console.rule("[bold yellow]💬 المرحلة 3: تطبيقات المراسلة")
        
        msg_table = Table(show_header=True, box=box.SIMPLE, header_style="bold magenta")
        msg_table.add_column("المنصة", style="cyan", width=16)
        msg_table.add_column("الحالة", style="white", width=20)
        msg_table.add_column("الرابط / ملاحظة", style="dim", width=50)
        
        wa_status = whatsapp.get("status", "❌")
        tg_status = telegram.get("status", "❌")
        
        msg_table.add_row("📱 WhatsApp", wa_status, whatsapp.get("url", ""))
        msg_table.add_row("✈️ Telegram", tg_status, telegram.get("url", ""))
        msg_table.add_row("📘 Facebook", facebook_res, "يتطلب تسجيل دخول لرؤية الملف")
        console.print(msg_table)
        
        # ---- PHASE 4: Phone Directories ----
        console.rule("[bold yellow]📖 المرحلة 4: أدلة الأرقام العربية")
        
        dir_table = Table(show_header=True, box=box.SIMPLE, header_style="bold green")
        dir_table.add_column("المصدر", style="cyan", width=22)
        dir_table.add_column("النتيجة", style="white", width=30)
        dir_table.add_column("ملاحظة", style="dim", width=45)
        
        dir_table.add_row("🇸🇦 دليل أرقام السعودية", ksanums.get("status", "⚠️"), 
                         ksanums.get("raw", "")[:80] if "raw" in ksanums else "")
        dir_table.add_row("🔍 Numberozo", numberozo_result.get("status", "⚠️"),
                         numberozo_result.get("raw", "")[:80] if "raw" in numberozo_result else "")
        dir_table.add_row("📗 NumberBook Saudi", numbook.get("status", "⚠️"),
                         numbook.get("raw", "")[:80] if "raw" in numbook else "")
        dir_table.add_row("🆔 Truecaller", truecaller.get("status", "⚠️"),
                         f"رمز الحالة: {truecaller.get('status_code', 'N/A')}" if "status_code" in truecaller else "محجوب في السعودية")
        console.print(dir_table)
        
        # ---- PHASE 5: Web Search ----
        console.rule("[bold yellow]🌐 المرحلة 5: البحث في الويب")
        
        web_table = Table(show_header=True, box=box.SIMPLE, header_style="bold blue")
        web_table.add_column("المصدر", style="cyan", width=12)
        web_table.add_column("النتائج", style="white", width=70)
        
        # Google
        google_text = ""
        for r in google_res:
            for s in r.get("snippets", []):
                google_text += f"• {s[:100]}\n"
        web_table.add_row("Google", google_text.strip() or "لا نتائج عامة")
        
        # Bing
        bing_text = "\n".join([f"• {b[:100]}" for b in bing_res if b != "لا نتائج"])
        web_table.add_row("Bing", bing_text.strip() or "لا نتائج عامة")
        
        # Pastebin
        for p in pastebin_res:
            cnt = p.get("count", 0)
            src = p.get("source", "paste")
            web_table.add_row("📄 Pastebin", f"{cnt} تسريبة في {src}" if cnt else "لا توجد تسريبات")
        
        console.print(web_table)
        
        # ---- PHASE 6: Social Media ----
        console.rule("[bold yellow]📱 المرحلة 6: وسائل التواصل الاجتماعي")
        
        social_table = Table(show_header=True, box=box.SIMPLE, header_style="bold yellow")
        social_table.add_column("المنصة", style="cyan", width=14)
        social_table.add_column("النتيجة", style="white", width=40)
        social_table.add_column("نصيحة", style="dim", width=45)
        
        for platform, result in social_res.items():
            tip = {
                "Instagram": "ابحث يدويًا عن الرقم في التطبيق",
                "X/Twitter": "ابحث عن @username بالرقم",
                "TikTok": "قد لا يظهر بدون حساب",
                "Snapchat": "جرب إضافة الرقم كصديق",
            }.get(platform, "")
            social_table.add_row(platform, result, tip)
        
        console.print(social_table)
        
        # ---- PHASE 7: Breach Data ----
        console.rule("[bold yellow]🔐 المرحلة 7: تسريبات البيانات")
        
        breach_text = ""
        if isinstance(leakcheck_res, dict):
            for k, v in leakcheck_res.items():
                breach_text += f"• {k}: {v}\n"
        else:
            breach_text = str(leakcheck_res)
        
        console.print(Panel(breach_text or "لا توجد بيانات تسريب معروفة", 
                           title="🔓 نتائج فحص التسريبات", border_style="red"))
        
        # ---- FINAL REPORT ----
        console.rule("[bold red]📊 التقرير النهائي")
        
        # Calculate score
        score = 0
        score += 10 if "موجود" in whatsapp.get("status", "") else 0
        score += 10 if "موجود" in telegram.get("status", "") else 0
        score += 15 if "موجود" in ksanums.get("status", "") or "بيانات" in ksanums.get("status", "") else 0
        score += 15 if "موجود" in numberozo_result.get("status", "") or "بيانات" in numberozo_result.get("status", "") else 0
        score += 10 if "موجود" in numbook.get("status", "") else 0
        score += 10 if "تم العثور" in truecaller.get("status", "") else 0
        score += 5 if any(s["count"] > 0 if isinstance(s, dict) and "count" in s else False for s in pastebin_res) else 0
        
        # Social media score
        for v in social_res.values():
            if "✅" in v or "حساب" in v:
                score += 5
        
        level = "🟢 منخفض" if score < 20 else "🟡 متوسط" if score < 50 else "🔴 مرتفع"
        
        summary = Panel(
            f"[bold]رقم:[/bold] {FMT_INTL}\n"
            f"[bold]الشبكة:[/bold] {carrier_info[0]}\n"
            f"[bold]درجة الظهور الرقمي:[/bold] {level} ({score}/100)\n\n"
            f"{'✅ الرقم مسجل في واتساب' if 'موجود' in whatsapp.get('status','') else '❌ الرقم غير مسجل في واتساب'}\n"
            f"{'✅ الرقم مسجل في تيليغرام' if 'موجود' in telegram.get('status','') else '❌ الرقم غير مسجل في تيليغرام'}\n\n"
            f"[bold]التوصية:[/bold]\n"
            f"1. استخدم واتساب للتواصل (إن وجد)\n"
            f"2. جرب البحث على Instagram/Snapchat يدويًا\n"
            f"3. استخدم Truecaller مع VPN على هاتفك\n"
            f"4. للإبلاغ عن مكالمات احتيال: https://e-crime.gov.sa",
            title="🎯 الخلاصة", border_style="green"
        )
        console.print(summary)
        
        # ---- LINUX TOOLKIT ----
        console.rule("[bold green]🛠 أوامر لينكس إضافية يمكنك تشغيلها")
        
        toolkit = f"""
```bash
# 1. أداة theHarvester (مثبتة)
theHarvester -d {LOCAL} -b yahoo,bing

# 2. استخبارات DNS
dig -x {LOCAL[:3]}.{LOCAL[3:]} +short 2>/dev/null || echo "لا يوجد reverse DNS"

# 3. WHOIS للرقم (إن كان أرضي)
whois {FMT_SAUDI} 2>/dev/null

# 4. فحص واتساب (curl)
curl -sI "https://wa.me/{COUNTRY_CODE}{LOCAL}" | head -5

# 5. تثبيت PhoneInfoga للبحث العميق
git clone https://github.com/sundowndev/phoneinfoga.git
cd phoneinfoga
pip3 install -r requirements.txt
python3 phoneinfoga.py -n "{FMT_INTL}"

# 6. البحث في Google Dorks
firefox "https://www.google.com/search?q=%22+966542885769%22+%D8%B3%D8%B9%D9%88%D8%AF%D9%8A" 2>/dev/null

# 7. البحث في Bing
firefox "https://www.bing.com/search?q=0542885769" 2>/dev/null
```
        """
        console.print(Markdown(toolkit))


if __name__ == "__main__":
    asyncio.run(main())
