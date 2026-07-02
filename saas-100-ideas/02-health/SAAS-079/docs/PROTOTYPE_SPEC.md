# PROTOTYPE SPEC — PharmacyRx (SAAS-079)
> Owner: UI/UX Designer · Gate 2

## Screen: Prescription Upload & OCR (maps to Journey Stage: تصوير روشتة)
- **Layout:** Camera viewfinder with guide overlay + manual entry fallback
- **Components:** CameraView (document scanner mode), FlashToggle, GuideOverlay (fit the prescription), OCRResultCard (extracted medicines), CorrectionInput, ConfirmButton
- **States:** Empty → "صوّر الوصفة الطبية"; Loading → Processing OCR…; Error → "لم نتمكن من قراءة الوصفة" → manual entry; Edge → Prescription contains controlled substance → "هذا الدواء يتطلب وصفة أصلية للصرف" → flag pharmacy
- **Key Interaction:** Hold camera steady → auto-capture when clear → review OCR → confirm
- **Friction Resolved:** #1 — OCR handles unclear handwriting with confidence score

## Screen: Federated Medicine Search (maps to Journey Stage: بحث)
- **Layout:** Search bar + pharmacy chip filters + results list + map
- **Components:** SearchBar (with suggestions), PharmacyFilterChips (sorted by distance/price), MedicineResultCard (name, nearest pharmacy, price, stock), MiniMapView, PriceHistoryChart
- **States:** Empty → Search for a medicine…; Loading → Spinner; Error → "الدواء غير متوفر في أي صيدلية" → suggest alternatives; Edge → Medicine available but controlled → show availability without price
- **Key Interaction:** Tap result → show which pharmacies have it sorted by distance
- **Friction Resolved:** #2 — Federated search across all pharmacies in one query

## Screen: Pharmacist Consultation (maps to Journey Stage: استشارة)
- **Layout:** Pre-consultation questionnaire → video/chat room → post-consultation summary
- **Components:** SymptomQuestionnaire, ConsultationTypeToggle (chat/video), VideoCallView, ChatBubble, MedicineRecommendCard, FollowUpScheduler
- **States:** Empty → "ابدأ استشارة مع صيدلي"; Loading → Finding available pharmacist; Error → "جميع الصيادلة مشغولون" → queue wait time; Edge → Consultation fee refund if pharmacist doesn't respond in 5min
- **Key Interaction:** Chat → share medicine photo → pharmacist recommends
- **Friction Resolved:** #5 — Easy access to pharmacist consultation without clinic visit

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| DocumentScanner | photo, file-upload | idle/capturing/processing | Auto-detect edges |
| OCRResultCard | verified, needs-review | confirmed/editing | Confidence score badge |
| MedicineCard | available, unavailable, alternative | with-price/without | Stock indicator dot |
| PharmacyChip | nearest, cheapest, fastest | unselected/selected | Sorted by preference |
| ConsultationRoom | video, chat, voice | waiting/active/ended | Timer + quality rating |
| AdherenceCard | taken, missed, upcoming | with-reminder | Streak counter |
| InsuranceCard | active, expired, pending | verified/pending/rejected | Coverage summary |
