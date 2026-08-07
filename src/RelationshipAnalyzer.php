<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

/**
 * Relationship Analyzer Service
 *
 * Single Responsibility: Analyze and categorize beneficiary relationships
 * using the RelationshipLookup service.
 */
class RelationshipAnalyzer
{
    private RelationshipLookup $relationshipLookup;

    public function __construct(RelationshipLookup $relationshipLookup)
    {
        $this->relationshipLookup = $relationshipLookup;
    }

    /**
     * Analyze beneficiary relationships and categorize them
     *
     * @param array $beneficiaries List of beneficiary data
     * @return array Analysis results with breakdown, details, and insights
     */
    public function analyzeRelationships(array $beneficiaries): array
    {
        $relationships = $this->initializeRelationshipCategories();

        foreach ($beneficiaries as $beneficiary) {
            $category = $this->categorizeBeneficiary($beneficiary);
            if ($category) {
                $relationships[$category][] = $beneficiary;
            }
        }

        return [
            'breakdown' => array_map(fn($group) => count($group), $relationships),
            'details' => $relationships,
            'insights' => $this->generateRelationshipInsights($relationships)
        ];
    }

    /**
     * Categorize a single beneficiary based on their relationship
     *
     * @param array $beneficiary Beneficiary data
     * @return string|null The category name or null if not found
     */
    public function categorizeBeneficiary(array $beneficiary): ?string
    {
        $relationshipTerm = $beneficiary['relationship'] ?? '';

        if (empty($relationshipTerm)) {
            return 'other';
        }

        $categoryInfo = $this->relationshipLookup->getRelationshipCategory($relationshipTerm);

        return $categoryInfo ? $categoryInfo['category'] : 'other';
    }

    /**
     * Get inheritance priority for a relationship
     *
     * @param string $relationshipTerm The relationship term
     * @return int The inheritance priority (higher = more priority)
     */
    public function getInheritancePriority(string $relationshipTerm): int
    {
        $categoryInfo = $this->relationshipLookup->getRelationshipCategory($relationshipTerm);
        return $categoryInfo ? (int)$categoryInfo['inheritance_priority'] : 0;
    }

    /**
     * Get tax implications for a relationship
     *
     * @param string $relationshipTerm The relationship term
     * @return string Tax implications description
     */
    public function getTaxImplications(string $relationshipTerm): string
    {
        $categoryInfo = $this->relationshipLookup->getRelationshipCategory($relationshipTerm);
        return $categoryInfo ? ($categoryInfo['tax_implications'] ?? '') : '';
    }

    /**
     * Initialize the relationship categories array
     *
     * @return array Initialized categories array
     */
    private function initializeRelationshipCategories(): array
    {
        return [
            'spouse' => [],
            'children' => [],
            'parents' => [],
            'siblings' => [],
            'step_relatives' => [],
            'adopted_relatives' => [],
            'other' => []
        ];
    }

    /**
     * Generate insights based on relationship analysis
     *
     * @param array $relationships Categorized relationships
     * @return array List of insights
     */
    private function generateRelationshipInsights(array $relationships): array
    {
        $insights = [];

        // Check for spousal relationships
        if (empty($relationships['spouse']) && !empty($relationships['children'])) {
            $insights[] = 'No spousal beneficiary identified - consider tax implications of non-spouse designations';
        }

        // Check for children
        if (count($relationships['children']) > 0) {
            $insights[] = count($relationships['children']) . ' children identified as potential beneficiaries';
        }

        // Check for step/adopted relationships
        $stepAdoptedCount = count($relationships['step_relatives']) + count($relationships['adopted_relatives']);
        if ($stepAdoptedCount > 0) {
            $insights[] = $stepAdoptedCount . ' step/adopted relatives identified - review inheritance laws for these relationships';
        }

        // Check for non-family beneficiaries
        if (!empty($relationships['other'])) {
            $insights[] = 'Non-family beneficiaries identified - ensure proper estate planning documentation';
        }

        // Check for complex family structures
        $totalRelatives = array_sum(array_map(fn($group) => count($group), $relationships));
        if ($totalRelatives > 5) {
            $insights[] = 'Complex family structure detected - consider professional estate planning advice';
        }

        return $insights;
    }

    /**
     * Get all beneficiaries in a specific category
     *
     * @param array $beneficiaries List of all beneficiaries
     * @param string $category The category to filter by
     * @return array Beneficiaries in the specified category
     */
    public function getBeneficiariesByCategory(array $beneficiaries, string $category): array
    {
        return array_filter($beneficiaries, function($beneficiary) use ($category) {
            return $this->categorizeBeneficiary($beneficiary) === $category;
        });
    }
}