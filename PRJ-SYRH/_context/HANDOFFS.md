# HANDOFFS — PRJ-SYRH

## Next ticket
E2E browser QA — verify ALL real-time broadcast paths + frontend fixes:
1. Open `test@user.com` + `agency@test.com` in two browser windows
2. User sends message → verify real-time (no refresh) on agency side
3. Agency sends message → verify real-time on user side
4. Test offer/negotiation flow (send → accept/reject/counter) → verify both sides
5. Test agency payment request → verify user sees Pay Now button
6. Test quick replies → verify real-time delivery on user side
7. Test multiple conversations → verify Echo channels don't cross
8. Verify sendError banner shows on network failure (UserChat)
9. Verify no console errors or unhandled promise rejections
10. Check Reverb health: PID running, port 8081, Caddy proxy /app/* + /apps/* routes

## Backlog
- Add more PHP feature tests for remaining API controllers
- Update Login.tsx to preserve ?redirect param for post-login redirect
- Clean up old empty-title properties (IDs 9-14) from DB
