# PROTOTYPE SPEC — ClinicFlow (SAAS-002)
> Owner: UI/UX Designer · Gate 2

## Screen 1: Appointment Booking (maps to: Register → Book)
- **Layout:** Top: search bar + "New Patient" button, Main: date navigator + time slots grid, Bottom: appointment summary card
- **Components:** Search input, Patient card (name, phone, last visit), Date picker (horizontal scroll), Time slot grid (30min intervals), Status badges
- **States:**
  - Empty: "No appointments today. Start by searching or adding a patient."
  - Loading: Calendar skeleton shimmer, slot spinner
  - Error: Slot booking conflict → inline error + suggest next available
  - Edge: Multi-step booking for new patients, quick booking for returning
- **Key Interaction:** Select date → view slots → select patient → tap slot → confirm
- **Friction Resolved:** #3, #4 — smart search + conflict prevention

## Screen 2: Patient Profile & History (maps to: Register → Consult)
- **Layout:** Header: patient photo, name, file number, Contact: phone, DOB, blood type, Medical timeline: reverse-chronological visit cards, Quick actions: New appointment, Add record, Prescribe
- **Components:** Avatar, Info grid, Timeline component, Action buttons, Search within records
- **States:**
  - Empty: "No visit history. Record first visit here."
  - Loading: Profile skeleton, timeline cards shimmer
  - Error: Patient fetch error → retry button
  - Edge: Long treatment history → lazy-load + search filter
- **Key Interaction:** Scroll timeline → tap visit card → expand details
- **Friction Resolved:** #2 — instant access to full history

## Screen 3: SOAP Note / Medical Record (maps to: Consult → Diagnosis)
- **Layout:** Form with sections — S (Subjective): complaint input, O (Objective): vitals, exam findings, A (Assessment): diagnosis picker, P (Plan): treatment, medications
- **Components:** Rich text input, Vitals input row (BP, pulse, temp, weight), ICD-10 diagnosis search, Medication autocomplete, Save/Submit buttons
- **States:**
  - Empty: Blank SOAP template
  - Loading: Diagnosis list loading
  - Error: Save conflict → version warning
  - Edge: Template per specialty (dental template vs general practice)
- **Key Interaction:** Type complaint → auto-suggest diagnoses → select → save
- **Friction Resolved:** #5 — structured digital record replaces paper

## Screen 4: Invoice & Payment (maps to: Invoice → Goal)
- **Layout:** Left: services list with prices, Right: invoice summary (subtotal, tax, discount, total), Bottom: payment method selector (cash/card/insurance)
- **Components:** Service selector (checkboxes), Price input, Tax toggle, Insurance claim fields, Payment method cards, Print/Email receipt
- **States:**
  - Empty: No services selected
  - Loading: Generating invoice PDF
  - Error: Payment gateway failure → fallback to cash
  - Edge: Split payment (partial cash + card), insurance co-pay
- **Key Interaction:** Check services → see total update live → select payment → issue receipt
- **Friction Resolved:** #5 — automated billing

## Component Library
| Component | Variants | States | Behaviour |
|-----------|----------|--------|-----------|
| Appointment Card | Upcoming, Completed, Cancelled | default/hover/selected | color-coded left border (blue/yellow/gray) |
| Time Slot | Available, Booked, Blocked | default/hover/selected/disabled | 30min block, tap to select |
| Patient Search | Quick (phone), Advanced (name+file) | default/loading/results/empty | 200ms debounce, 6 results max |
| SOAP Form | Per specialty template | empty/filled/saving/error/saved | auto-save after 30s idle |
| Vitals Input | BP, Pulse, Temp, Weight, Oxygen | normal/warning/critical | color indicator (green/yellow/red) |
| Invoice Summary | Simple (cash), Insurance | empty/calculated/paid/pending | real-time calculation |
| Action Button | Primary (blue), Danger (red), Ghost | default/hover/active/disabled | 8px radius, icon+label |
