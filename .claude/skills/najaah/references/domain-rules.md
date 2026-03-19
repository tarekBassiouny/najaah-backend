# Domain Rules

## Playback Sessions
- Playback starts by issuing a short-lived embed token and an active session.
- Progress updates extend the active session.
- A session becomes a counted view when progress reaches the full-play threshold.
- Stale sessions time out after inactivity and must be closed or cleaned up.

## View Limits
- Default behavior is limited views per video.
- A view is counted only when the session reaches full-play status.
- Remaining views are resolved from the effective settings hierarchy plus approved extra views.
- When remaining views reach zero, playback should be blocked through the service layer.

## Device Policy
- Students have one active device at a time.
- First login registers the device.
- Device switches require a change request unless reinstall detection allows a safe update path.
- Tokens and protected actions must remain device-aware.

## Settings Hierarchy
Later overrides earlier:
1. Center defaults
2. Center settings JSON
3. Course settings JSON
4. Video or course pivot overrides
5. Student-specific settings

## Multi-Tenancy
- System routes operate under `/api/v1/admin/...`
- Center routes operate under `/api/v1/admin/centers/{center}/...`
- Center context comes from the route when the route is center-scoped.
- Never rely on a body or query `center_id` when the route path already defines the center.
