# FR-006-005 RelationshipLookup: Relationship Lookup

**Related:** BR-006 Recommendation, UC-006-001 RecommendationUseCases
**Engine:** `Ksfraser\\Recommendation\\RelationshipLookup`

## Description
Look up relationship data.

## Primary actor
Advisor (or System on recalculation).

## Preconditions
Client data available in FA.

## Main flow
1. Advisor invokes the calculation for the client.
2. System applies the engine and returns the result.

## Postconditions
Calculation result available for the client's plan summary.
