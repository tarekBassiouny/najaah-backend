# Allow Playback on Web

## Objective

Add a center-level setting to allow students to watch videos in a web browser, not just on the mobile app. Currently playback is mobile-only (requires a registered device via JWT + device binding).

## Current State

### How Playback Works Today

1. Student authenticates via mobile JWT + device binding
2. `PlaybackAuthorizationService` validates:
   - Student has active enrollment
   - Student has an active device (`UserDevice`)
   - View limits not exceeded
3. `PlaybackService::requestPlayback()` creates a `PlaybackSession` linked to `device_id`
4. For Bunny videos: `BunnyEmbedTokenService` generates signed embed URL
5. For URL videos: returns direct URL with session tracking

### Why It's Mobile-Only

- `PlaybackAuthorizationService::resolveActiveDevice()` **requires** an active device from `user_devices` table
- Throws `NO_ACTIVE_DEVICE` if none found
- Device is registered during mobile login via `DeviceBindingService`
- Web browsers don't go through device registration
- Every `PlaybackSession` has a required `device_id` FK

### No Existing Web Settings

- No `allow_web_playback` in any settings
- No device_type checks in playback flow
- No web player frontend exists (only mobile app consumes embed URLs)

## Architecture Decision

**Approach**: Add `allow_web_playback` as a center setting. When enabled, playback endpoints accept a virtual "web" device context instead of requiring a registered physical device.

**NOT changing**: The core playback flow stays the same. We're adding an alternate path for device resolution, not replacing it.

## Implementation Plan

### Phase 1: Settings (Architecture)

1. **Add to PolicySettingsService catalog**:
   ```php
   'allow_web_playback' => [
       'scope' => 'center',
       'type' => 'boolean',
       'storage' => 'center_settings.settings.allow_web_playback',
       'default' => false,
   ]
   ```

2. **Add to center settings request**: `UpdateCenterSettingsRequest` — `settings.allow_web_playback` (boolean)

3. **Add to SettingsResolverService**: Include in `$allowedKeys`

4. **Seed/factory**: Default `false` (opt-in per center)

5. **Migration**: Add default value to existing center_settings JSON

### Phase 2: Device Resolution (Features)

**Key change**: `PlaybackAuthorizationService::resolveActiveDevice()`

Current flow:
```
resolveActiveDevice(User) → UserDevice (required)
    ↓ throws NO_ACTIVE_DEVICE if none
```

New flow:
```
resolveActiveDevice(User, ?Center) → UserDevice|VirtualWebDevice
    ↓ if no physical device AND center.allow_web_playback:
        → create/return a virtual "web" device context
    ↓ if no physical device AND !allow_web_playback:
        → throw NO_ACTIVE_DEVICE (existing behavior)
```

**Options for "virtual web device"**:

**Option A: Nullable device_id on PlaybackSession** (simpler)
- Make `device_id` nullable on `playback_sessions`
- When web playback: `device_id = null`
- Pro: Minimal schema change
- Con: Loses device tracking for web sessions

**Option B: Auto-create web device record** (recommended)
- When web playback enabled and no device exists:
  - Create a `UserDevice` with `device_type = 'web'`, `device_name = 'Web Browser'`
  - Reuse for subsequent web sessions
- Pro: All existing code works unchanged (sessions still have device_id)
- Con: Pollutes device table with virtual entries, counts against device_limit

**Option C: Exempt web from device binding entirely**
- Skip device check when request comes from web + setting enabled
- Create PlaybackSession with a sentinel device_id
- Pro: Clean separation
- Con: Most code change

**Recommendation**: Option B — auto-create web device. It's the least disruptive.

### Phase 3: Web Detection (API)

How do we know a request is from "web" vs "mobile"?

**Approach**: Check for JWT device context. Mobile requests have `authenticated_device` in request attributes (set by `JwtMobileMiddleware`). Web requests authenticated via Sanctum/session don't have this.

```php
// In PlaybackAuthorizationService
$isWebRequest = ! $request->attributes->has('authenticated_device');
$isWebAllowed = $this->settingsResolver->resolve(...)['allow_web_playback'] ?? false;

if ($isWebRequest && $isWebAllowed) {
    return $this->getOrCreateWebDevice($user);
}
```

**Question for you**: Will web students authenticate via Sanctum sessions (like admin) or via a new web JWT flow? This affects which middleware and auth guard the playback endpoints use.

### Phase 4: View Limits on Web

Web playback should still respect view limits. The existing `PlaybackAuthorizationService` already counts views per session regardless of device type, so this works automatically.

**However**: `device_limit` setting controls max active devices per student. A web "device" would count against this limit. We should either:

- A) Exempt web devices from device_limit count
- B) Count them (student uses 1 of their N slots for web)
- C) Add separate `web_device_limit` setting

**Recommendation**: Option A — exempt web from device_limit. Web is an additional access channel, not a competing device slot.

### Phase 5: Feature Flag

Gate behind a new feature flag: `features.web_playback` (system admin enables per center). Plus the center setting `allow_web_playback` (center admin enables within their center).

This gives two-level control:
- System admin: "This center can offer web playback"
- Center admin: "I want to enable web playback for my students"

---

## Files to Create

| File | Type |
|------|------|
| Migration for `allow_web_playback` default in center_settings | Migration |

## Files to Modify

| File | Change |
|------|--------|
| `PolicySettingsService.php` | Add `allow_web_playback` to catalog |
| `UpdateCenterSettingsRequest.php` | Add `allow_web_playback` validation |
| `SettingsResolverService.php` | Add to `$allowedKeys` |
| `PlaybackAuthorizationService.php` | Add web device resolution path |
| `CenterSettingFactory.php` / `CenterSettingSeeder.php` | Add default |

## Open Questions (Need Your Decision)

1. **Web auth method**: Sanctum session? New web JWT? Shared mobile JWT?
2. **Device limit**: Should web device count against `device_limit`? (Recommend: No)
3. **Feature flag**: Add `features.web_playback` to the center features system? (Recommend: Yes)
4. **View limit behavior**: Same as mobile? Different? (Recommend: Same)
5. **Concurrent sessions**: Can student watch on mobile AND web simultaneously? (Recommend: Yes if device_limit allows)
6. **Web player frontend**: Is there a web frontend being built? Or just API preparation?

## Risks

| Risk | Mitigation |
|------|------------|
| Students bypass mobile app DRM via web | Setting is per-center opt-in, not default |
| Device table pollution with web records | Filter by `device_type != 'web'` where needed |
| View limit bypass (mobile + web) | Views tracked per session regardless of device |
| Security: web has no device binding | Embed tokens still required, sessions still tracked |
| Existing concurrent device checks break | Exempt `device_type = 'web'` from concurrent checks |
