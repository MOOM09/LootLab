<?php

class PromoCode
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    //? Find a promo code by its code, ignoring letter case.
    public function findByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT promo_id, code, discount_type, discount_value, expires_at, created_at
             FROM promo_codes
             WHERE code = :code
             LIMIT 1'
        );

        $statement->execute(['code' => $code]);

        $promo = $statement->fetch(PDO::FETCH_ASSOC);

        return $promo === false ? null : $promo;
    }

    //? Find a promo code only when it is still usable (not expired).
    //? A NULL expires_at means the code never expires.
    public function findValidByCode(string $code): ?array
    {
        $promo = $this->findByCode($code);

        if ($promo === null) {
            return null;
        }

        if (
            $promo['expires_at'] !== null
            && strtotime((string) $promo['expires_at']) <= time()
        ) {
            return null;
        }

        if (!$this->hasValidDiscount($promo)) {
            return null;
        }

        return $promo;
    }

    //? The stored row must still satisfy the schema rules.
    private function hasValidDiscount(array $promo): bool
    {
        $type = (string) ($promo['discount_type'] ?? '');
        $value = (float) ($promo['discount_value'] ?? 0);

        if (!in_array($type, ['percentage', 'fixed'], true)) {
            return false;
        }

        if ($value <= 0) {
            return false;
        }

        if ($type === 'percentage' && $value > 100) {
            return false;
        }

        return true;
    }
}
