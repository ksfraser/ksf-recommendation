# ksf_recommendation

Recommendation calculation engines — business logic. Part of the KSF calculation package family.

- Namespace: `Ksfraser\Recommendation`
- Framework: `ksfraser/ksf_modules_common` (`Ksfraser\ModulesCommon`)
- Exceptions: `ksfraser/exceptions`

## Engines
| Engine | Purpose |
|--------|---------|
| `RecommendationEngine` | Generate planning recommendations. |
| `RecommendationGenerator` | Generate recommendation narratives. |
| `RecommendationLookup` | Look up recommendation rules. |
| `RelationshipAnalyzer` | Analyze client relationships for planning. |
| `RelationshipLookup` | Look up relationship data. |
| `RiskAdjustedRecommendationEngine` | Generate risk-adjusted recommendations. |

## Requirements (BABOK)
- `Requirements/BR-006 Recommendation.md`
- `Requirements/FR-006-001..006 *.md`
- `Requirements/UC-006-001 RecommendationUseCases.md`

## Status
Scaffold — engines extracted and namespaced.
