<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

/**
 * Recommendation Generator Service
 *
 * Single Responsibility: Generate beneficiary analysis recommendations
 * using the RecommendationLookup service and analysis context.
 */
class RecommendationGenerator
{
    private RecommendationLookup $recommendationLookup;

    public function __construct(RecommendationLookup $recommendationLookup)
    {
        $this->recommendationLookup = $recommendationLookup;
    }

    /**
     * Generate recommendations based on beneficiary analysis
     *
     * @param array $beneficiaries List of beneficiary data
     * @param array $accounts List of account data
     * @param array $estateData Estate composition data
     * @return array List of applicable recommendations
     */
    public function generateRecommendations(array $beneficiaries, array $accounts, array $estateData): array
    {
        $recommendations = [];

        // Build context for trigger condition evaluation
        $context = $this->buildAnalysisContext($beneficiaries, $accounts, $estateData);

        // Get applicable recommendations from database
        $applicableRecommendations = $this->recommendationLookup->getApplicableRecommendations($context);

        foreach ($applicableRecommendations as $rec) {
            $recommendation = $this->customizeRecommendation($rec, $context);
            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }

        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            return ($priorityOrder[$a['priority']] ?? 5) <=> ($priorityOrder[$b['priority']] ?? 5);
        });

        return $recommendations;
    }

    /**
     * Build analysis context for trigger condition evaluation
     *
     * @param array $beneficiaries List of beneficiary data
     * @param array $accounts List of account data
     * @param array $estateData Estate composition data
     * @return array Context data
     */
    private function buildAnalysisContext(array $beneficiaries, array $accounts, array $estateData): array
    {
        $context = [
            'total_beneficiaries' => count($beneficiaries),
            'total_accounts' => count($accounts),
            'designated_accounts' => count(array_filter($accounts, fn($acc) => !empty($acc['beneficiaries']))),
            'undesignated_accounts' => count(array_filter($accounts, fn($acc) => empty($acc['beneficiaries']))),
            'outdated_accounts' => count(array_filter($accounts, fn($acc) => $this->isOutdated($acc))),
            'missing_contingents' => count($this->getMissingContingents($accounts)),
            'minor_beneficiaries' => count(array_filter($beneficiaries, fn($b) => ($b['age'] ?? 25) < 18)),
            'high_concentration_risk' => $this->calculateConcentrationRisk($beneficiaries, $accounts)['high_concentration'],
            'retirement_to_estate' => count($this->getRetirementToEstate($accounts)),
            'has_spouse' => !empty(array_filter($beneficiaries, fn($b) => in_array(strtolower($b['relationship'] ?? ''), ['spouse', 'husband', 'wife']))),
        ];

        return $context;
    }

    /**
     * Customize a recommendation with specific context data
     *
     * @param array $recommendation Base recommendation from database
     * @param array $context Analysis context
     * @return array|null Customized recommendation or null if not applicable
     */
    private function customizeRecommendation(array $recommendation, array $context): ?array
    {
        $customized = [
            'priority' => $recommendation['priority'],
            'category' => $recommendation['category'],
            'title' => $recommendation['title'],
            'description' => $recommendation['description'],
            'rationale' => $recommendation['rationale'],
        ];

        // Add specific data based on recommendation type
        switch ($recommendation['recommendation_code']) {
            case 'update_outdated_designations':
                $outdatedAccounts = array_filter($context['accounts'] ?? [], fn($acc) => $this->isOutdated($acc));
                if (!empty($outdatedAccounts)) {
                    $customized['accounts'] = array_column($outdatedAccounts, 'name');
                } else {
                    return null; // Not applicable
                }
                break;

            case 'add_contingent_beneficiaries':
                $missingContingents = $this->getMissingContingents($context['accounts'] ?? []);
                if (!empty($missingContingents)) {
                    $customized['accounts'] = array_column($missingContingents, 'name');
                } else {
                    return null;
                }
                break;

            case 'establish_trusts_for_minors':
                $minorBeneficiaries = array_filter($context['beneficiaries'] ?? [], fn($b) => ($b['age'] ?? 25) < 18);
                if (!empty($minorBeneficiaries)) {
                    $customized['beneficiaries'] = array_column($minorBeneficiaries, 'name');
                } else {
                    return null;
                }
                break;

            case 'diversify_beneficiary_designations':
                $concentrationRisk = $this->calculateConcentrationRisk($context['beneficiaries'] ?? [], $context['accounts'] ?? []);
                if ($concentrationRisk['high_concentration'] > 0) {
                    $customized['beneficiaries'] = $concentrationRisk['high_concentration_beneficiaries'];
                } else {
                    return null;
                }
                break;

            case 'change_retirement_to_individuals':
                $retirementToEstate = $this->getRetirementToEstate($context['accounts'] ?? []);
                if (!empty($retirementToEstate)) {
                    $customized['accounts'] = array_column($retirementToEstate, 'name');
                } else {
                    return null;
                }
                break;

            default:
                // For other recommendations, check if they have specific trigger conditions
                if (!$this->checkSpecificConditions($recommendation, $context)) {
                    return null;
                }
        }

        return $customized;
    }

    /**
     * Check specific conditions for recommendations that need custom logic
     *
     * @param array $recommendation The recommendation
     * @param array $context Analysis context
     * @return bool True if conditions are met
     */
    private function checkSpecificConditions(array $recommendation, array $context): bool
    {
        // This could be expanded for more complex recommendation logic
        // For now, return true for most recommendations
        return true;
    }

    // Helper methods
    private function isOutdated(array $account): bool
    {
        $designationDate = $account['designation_date'] ?? null;
        if (!$designationDate) return true;

        $date = strtotime($designationDate);
        $threeYearsAgo = strtotime('-3 years');

        return $date < $threeYearsAgo;
    }

    private function getMissingContingents(array $accounts): array
    {
        return array_filter($accounts, function($acc) {
            $beneficiaries = $acc['beneficiaries'] ?? [];
            $primaries = array_filter($beneficiaries, fn($b) => ($b['type'] ?? '') === 'primary');
            $contingents = array_filter($beneficiaries, fn($b) => ($b['type'] ?? '') === 'contingent');
            return !empty($primaries) && empty($contingents);
        });
    }

    private function calculateConcentrationRisk(array $beneficiaries, array $accounts): array
    {
        $concentration = [
            'high_concentration' => 0,
            'high_concentration_beneficiaries' => []
        ];

        foreach ($beneficiaries as $beneficiary) {
            $beneficiaryValue = 0;
            foreach ($accounts as $account) {
                foreach ($account['beneficiaries'] ?? [] as $accBeneficiary) {
                    if (($accBeneficiary['name'] ?? '') === ($beneficiary['name'] ?? '')) {
                        $beneficiaryValue += ($account['value'] ?? 0) / count($account['beneficiaries'] ?? [1]);
                    }
                }
            }

            if ($beneficiaryValue > 500000) {
                $concentration['high_concentration']++;
                $concentration['high_concentration_beneficiaries'][] = $beneficiary['name'];
            }
        }

        return $concentration;
    }

    private function getRetirementToEstate(array $accounts): array
    {
        return array_filter($accounts, function($acc) {
            return ($acc['type'] ?? '') === 'retirement' &&
                   isset($acc['payable_to_estate']) &&
                   $acc['payable_to_estate'] === true;
        });
    }
}