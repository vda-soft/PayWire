# PayWire — agents instructions

**Always start by reading `AGENTS.md` in the project root.** It contains all rules, scripts, conventions, and architecture guidelines for this repository.

---

## Project context (supplementary)

We're building a payment system to replace legacy Payum with a modern DDD-based solution. The codebase is **in-progress** — unfinished features, incomplete domain knowledge. Factor this in.

**Architecture reference:** <https://github.com/DomainDrivers/dd-php>.

## Directory structure (high-level)

* `src/PayWire/Core` — core PHP functionality for handling payments
* `src/PayWire/Bridge/*` — framework bridges (e.g., Symfony bundle)
* `src/PayWire/Gateway/*` — payment provider implementations (PayU, PayPal, etc.)

## Behavioral guidelines

* **Be critical** — of user requests and your own suggestions. Verify solutions make sense.
* **Challenge architectural decisions** — don't accept them at face value.