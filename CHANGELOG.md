# SoulTether Changelog

## [1.0.1] — 2026-06-04

### Added
- Withdraw button for outgoing pending requests — requester can cancel a sent request before it is approved or declined

### Fixed
- JavaScript event listeners now use `jQuery(document).ready()` instead of an IIFE wrapper, resolving compatibility issues with WordPress noConflict jQuery mode where click handlers were silently failing to bind

### Changed
- "Dissolve Partnership" renamed to **Untether** throughout
- Plugin rebranded from NWG Partner System to **SoulTether** for public release
- All NWG-specific references removed — fully generic for any OpenSim grid
- Unix socket support added to Robust DB connection (preferred when WP and Robust share a server)

---

## [1.0.0] — 2026-06-03

### Initial Release

- Send partner requests with optional message
- Friend list pulled live from Robust DB Friends table
- Accept / Decline incoming requests
- Partnership written to both avatars' `userprofile.profilePartner` in Robust DB
- Untether (dissolve) active partnerships — clears both sides
- Email notification to request recipient
- Admin panel with status filters and pagination
- Self-partner prevention
- w4os avatar name integration (`w4os_avatarname`, `w4os_firstname`, `w4os_lastname`)
- Shortcode `[soultether]`
