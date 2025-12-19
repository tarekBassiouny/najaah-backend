## OTP Provider Strategy (Decision)

OTP sending is intentionally limited to a single provider. WhatsApp is the first and only provider implemented today.

### Final Recommendation (Authoritative)
**Current design is correct for now**  
**Future multi-provider support is cleanly achievable**

This is a deliberate architectural decision, not a missing feature.

### Why WhatsApp-only is intentional
- It matches current product scope and operational needs.
- It avoids premature abstraction and keeps the OTP flow simple and reliable.
- It reduces failure modes while the system scales.

### Why fallback logic is deferred
- There is no current requirement for multi-provider redundancy.
- Fallback adds complexity in routing, observability, and error handling.
- The current interface is sufficient and does not block future changes.

### Future change (high level)
If redundancy is required, a localized refactor can introduce a small provider selection layer that delegates to multiple providers based on availability and policy. This change would be contained to the OTP service boundary without affecting the rest of the system.
