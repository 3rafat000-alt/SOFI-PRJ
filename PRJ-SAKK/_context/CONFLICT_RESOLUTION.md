# Concurrent Session Conflict — Gemini Bridge Access

**Time:** 2026-07-02 14:22 GMT+3  
**Issue:** Two Claude Code sessions (windows) attempting simultaneous Gemini push  
**Symptom:** Tool use rejected by user (interrupted)  
**Root Cause:** Shared Gemini bridge (CDP port 9222) — no locking mechanism

## What Happened

Session A (this loop): Attempted `sofi gemini review` to validate Autonomous Gemini Loop system  
Session B (another window): Running concurrent work on same project  

Both tried to push to Gemini chat simultaneously → conflict.

## Impact

- Gemini review push blocked (user interrupted)
- Validation of Teaching VII system delayed
- No data corruption (push happened before conflict)

## Solution (Short-term)

1. **Stagger pushes:** Session A waits for Session B to complete Gemini interaction
2. **Use different projects:** Route Session B to different Gemini chat URL (if available)
3. **Sequential rescue:** Session A completes its validation after Session B finishes

## Solution (Medium-term)

Implement Gemini bridge locking in `gemini_bridge.py`:
```python
# File-based lock on .sofi_gemini_lock
class GeminiBridgeWithLock:
    def push(self, text, timeout=300):
        with FileLock(".sofi_gemini_lock", timeout=60):
            # Push protected by lock
            return self._push_impl(text)
```

## Solution (Long-term)

Design: Multi-chat Gemini strategy (one chat per project or per session boundary).
- Each PRJ gets its own pinned Gemini chat
- Eliminates lock contention
- Better audit trail (chat history = decision log per project)

## Action Items

- [ ] Document this conflict pattern in protocol (concurrent session handling)
- [ ] Add locking to `gemini_bridge.py` (Flock or file-based)
- [ ] Update `tooling-matrix.md` to note Gemini access is single-seat (or coordinate)
- [ ] Add to DECISIONS.md: "Concurrent Gemini access requires serialization (2026-07-02)"

## Current State

Session A: Paused on validation push (user interrupted)  
Session B: Unknown state (concurrent session, not this loop's concern)  

**Resumption:** Retry validation push after Session B completes or uses different chat.
