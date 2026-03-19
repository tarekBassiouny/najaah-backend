# PR Checklist

## Review Pass
- changed files understood
- authorization and tenancy preserved
- API contract changes called out
- admin resources remain readable and locale-aware
- tests cover the changed behavior
- obvious N+1 or missing eager loads checked

## Validation Commands
```bash
./vendor/bin/sail composer quality
./vendor/bin/sail test
./vendor/bin/sail pint --test
./vendor/bin/sail composer phpstan
```

## Review Output
- findings ordered by severity
- open questions or assumptions
- short change summary
- recommendation: ready or needs follow-up
