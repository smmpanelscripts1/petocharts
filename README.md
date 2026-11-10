# Peto Dashboard Charts

Blueprint extension for Pterodactyl / Blueprint.

- Admin home: RAM / disk / CPU cards show live usage, allocated quotas, and node capacity, plus charts
- Admin home **Rapor** button: last 24h / 7 days of live usage (avg, peak, daily table, CSV)
- Samples are stored once per minute while Overview is open; kept for 7 days under `/app/var/petocharts` (or `PETOCHARTS_HISTORY`)
- Client dashboard (`/`): start / restart / stop on each server card, multi-select bulk bar
- Admin → Servers: live resource columns, inline power controls, bulk start / stop / restart / kill

## Version

`1.8.0`

## Install

Copy into Blueprint extensions and run `blueprint -install petocharts`.

<img width="1915" height="921" alt="image" src="https://github.com/user-attachments/assets/2e81116f-2773-4189-bf61-ddaaf7eb140c" />

