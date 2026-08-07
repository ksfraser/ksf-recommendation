<?php

declare(strict_types=1);

namespace Ksfraser\Recommendation;

/**
 * Relationship Lookup Service
 *
 * Single Responsibility: Look up beneficiary relationship types from database
 * and provide categorization mapping for relationship terms.
 */
class RelationshipLookup
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the category for a given relationship term
     *
     * @param string $relationshipTerm The relationship term (e.g., 'husband', 'son', 'mother')
     * @return array{category: string, type_code: string, inheritance_priority: int, tax_implications: string}|null
     */
    public function getRelationshipCategory(string $relationshipTerm): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                rt.category,
                rt.relationship_code as type_code,
                rt.inheritance_priority,
                rt.tax_implications,
                rt.display_name
            FROM beneficiary_relationship_mappings rm
            JOIN beneficiary_relationship_types rt ON rm.relationship_type_id = rt.relationship_type_id
            WHERE rm.relationship_term = ?
              AND rt.is_active = 1
            LIMIT 1
        ");

        $stmt->execute([strtolower(trim($relationshipTerm))]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get all active relationship types
     *
     * @return array List of all active relationship types
     */
    public function getAllRelationshipTypes(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                relationship_code,
                category,
                display_name,
                description,
                inheritance_priority,
                tax_implications,
                legal_considerations
            FROM beneficiary_relationship_types
            WHERE is_active = 1
            ORDER BY category, inheritance_priority DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get relationship terms for a specific category
     *
     * @param string $category The category (e.g., 'spouse', 'children')
     * @return array List of relationship terms for the category
     */
    public function getRelationshipTermsForCategory(string $category): array
    {
        $stmt = $this->pdo->prepare("
            SELECT rm.relationship_term, rm.is_primary, rt.display_name
            FROM beneficiary_relationship_mappings rm
            JOIN beneficiary_relationship_types rt ON rm.relationship_type_id = rt.relationship_type_id
            WHERE rt.category = ?
              AND rt.is_active = 1
            ORDER BY rm.is_primary DESC, rm.relationship_term
        ");

        $stmt->execute([$category]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if a relationship term exists in the database
     *
     * @param string $relationshipTerm The relationship term to check
     * @return bool True if the term exists
     */
    public function relationshipTermExists(string $relationshipTerm): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM beneficiary_relationship_mappings rm
            JOIN beneficiary_relationship_types rt ON rm.relationship_type_id = rt.relationship_type_id
            WHERE rm.relationship_term = ?
              AND rt.is_active = 1
        ");

        $stmt->execute([strtolower(trim($relationshipTerm))]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($result['count'] ?? 0) > 0;
    }
}