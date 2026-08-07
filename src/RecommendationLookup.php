<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

/**
 * Recommendation Lookup Service
 *
 * Single Responsibility: Look up beneficiary analysis recommendations from database
 * and provide recommendation templates for estate planning optimization.
 */
class RecommendationLookup
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all active recommendations
     *
     * @return array List of all active recommendations
     */
    public function getAllRecommendations(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                recommendation_code,
                priority,
                category,
                title,
                description,
                rationale,
                trigger_conditions,
                applicable_scenarios
            FROM beneficiary_recommendations
            WHERE is_active = 1
            ORDER BY
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                category,
                title
        ");

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recommendations by category
     *
     * @param string $category The category (e.g., 'maintenance', 'protection')
     * @return array List of recommendations for the category
     */
    public function getRecommendationsByCategory(string $category): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                recommendation_code,
                priority,
                title,
                description,
                rationale,
                trigger_conditions,
                applicable_scenarios
            FROM beneficiary_recommendations
            WHERE category = ?
              AND is_active = 1
            ORDER BY
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                title
        ");

        $stmt->execute([$category]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recommendations by priority
     *
     * @param string $priority The priority level ('low', 'medium', 'high', 'critical')
     * @return array List of recommendations for the priority level
     */
    public function getRecommendationsByPriority(string $priority): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                recommendation_code,
                category,
                title,
                description,
                rationale,
                trigger_conditions,
                applicable_scenarios
            FROM beneficiary_recommendations
            WHERE priority = ?
              AND is_active = 1
            ORDER BY category, title
        ");

        $stmt->execute([$priority]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get a specific recommendation by code
     *
     * @param string $recommendationCode The recommendation code
     * @return array|null The recommendation data or null if not found
     */
    public function getRecommendationByCode(string $recommendationCode): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                recommendation_code,
                priority,
                category,
                title,
                description,
                rationale,
                trigger_conditions,
                applicable_scenarios
            FROM beneficiary_recommendations
            WHERE recommendation_code = ?
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([$recommendationCode]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Check if a recommendation code exists
     *
     * @param string $recommendationCode The recommendation code to check
     * @return bool True if the recommendation exists and is active
     */
    public function recommendationExists(string $recommendationCode): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM beneficiary_recommendations
            WHERE recommendation_code = ?
              AND is_active = 1
        ");

        $stmt->execute([$recommendationCode]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Get recommendations that match trigger conditions
     *
     * @param array $context Context data for evaluating trigger conditions
     * @return array List of applicable recommendations
     */
    public function getApplicableRecommendations(array $context): array
    {
        $allRecommendations = $this->getAllRecommendations();
        $applicable = [];

        foreach ($allRecommendations as $recommendation) {
            if ($this->evaluateTriggerConditions($recommendation, $context)) {
                $applicable[] = $recommendation;
            }
        }

        return $applicable;
    }

    /**
     * Evaluate if trigger conditions are met for a recommendation
     *
     * @param array $recommendation The recommendation data
     * @param array $context Context data for evaluation
     * @return bool True if conditions are met
     */
    private function evaluateTriggerConditions(array $recommendation, array $context): bool
    {
        $conditions = json_decode($recommendation['trigger_conditions'] ?? '[]', true);

        if (empty($conditions)) {
            return true; // No conditions means always applicable
        }

        // Simple condition evaluation - in practice, this could be more sophisticated
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '';
            $value = $condition['value'] ?? null;

            if (!isset($context[$field])) {
                return false;
            }

            $actualValue = $context[$field];

            switch ($operator) {
                case 'equals':
                    if ($actualValue !== $value) return false;
                    break;
                case 'not_equals':
                    if ($actualValue === $value) return false;
                    break;
                case 'greater_than':
                    if ($actualValue <= $value) return false;
                    break;
                case 'less_than':
                    if ($actualValue >= $value) return false;
                    break;
                case 'contains':
                    if (!is_array($actualValue) || !in_array($value, $actualValue)) return false;
                    break;
                case 'not_empty':
                    if (empty($actualValue)) return false;
                    break;
                case 'empty':
                    if (!empty($actualValue)) return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }
}