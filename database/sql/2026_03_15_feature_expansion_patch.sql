ALTER TABLE client_profiles
    ADD COLUMN IF NOT EXISTS organization_name VARCHAR(180) NULL AFTER postal_code,
    ADD COLUMN IF NOT EXISTS preferred_fulfillment_type ENUM('pickup','delivery') NULL AFTER preferred_contact_method,
    ADD COLUMN IF NOT EXISTS saved_measurements_json LONGTEXT NULL AFTER preferred_fulfillment_type,
    ADD COLUMN IF NOT EXISTS default_garment_preferences_json LONGTEXT NULL AFTER saved_measurements_json,
    ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER default_garment_preferences_json;

CREATE TABLE IF NOT EXISTS design_customizations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    design_post_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    garment_type VARCHAR(100) NULL,
    placement_area VARCHAR(100) NULL,
    fabric_type VARCHAR(100) NULL,
    width_mm DECIMAL(10,2) NULL,
    height_mm DECIMAL(10,2) NULL,
    color_count INT NULL,
    stitch_count_estimate INT NULL,
    complexity_level ENUM('simple','standard','complex','premium') NOT NULL DEFAULT 'standard',
    special_styles_json LONGTEXT NULL,
    notes TEXT NULL,
    artwork_path VARCHAR(255) NULL,
    preview_path VARCHAR(255) NULL,
    status ENUM('draft','estimated','proof_ready','approved','archived') NOT NULL DEFAULT 'draft',
    estimated_base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estimated_total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pricing_breakdown_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_design_customizations_post (design_post_id),
    KEY idx_design_customizations_order (order_id),
    KEY idx_design_customizations_user (user_id),
    CONSTRAINT fk_design_customizations_post FOREIGN KEY (design_post_id) REFERENCES design_posts(id) ON DELETE SET NULL,
    CONSTRAINT fk_design_customizations_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_design_customizations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS design_proofs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    design_customization_id BIGINT UNSIGNED NOT NULL,
    proof_no INT UNSIGNED NOT NULL DEFAULT 1,
    generated_by BIGINT UNSIGNED NOT NULL,
    preview_file_path VARCHAR(255) NOT NULL,
    annotated_notes TEXT NULL,
    pricing_snapshot_json LONGTEXT NULL,
    status ENUM('pending_client','approved','rejected','superseded') NOT NULL DEFAULT 'pending_client',
    responded_by BIGINT UNSIGNED NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_design_proofs_customization (design_customization_id),
    CONSTRAINT fk_design_proofs_customization FOREIGN KEY (design_customization_id) REFERENCES design_customizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_design_proofs_generated_by FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_design_proofs_responded_by FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_suggestion_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rule_code VARCHAR(80) NOT NULL UNIQUE,
    rule_name VARCHAR(180) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    amount_type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
    amount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    conditions_json LONGTEXT NULL,
    priority INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO price_suggestion_rules (rule_code, rule_name, category, amount_type, amount_value, conditions_json, priority, is_active, created_at, updated_at)
VALUES
('RUSH_MARKUP', 'Rush markup baseline', 'speed', 'percent', 15.00, '{"design_type":null}', 5, 1, NOW(), NOW()),
('PREMIUM_COMPLEXITY', 'Premium complexity markup', 'complexity', 'percent', 12.00, '{"complexity_level":"premium"}', 10, 1, NOW(), NOW()),
('BULK_PREP', 'Bulk preparation fee', 'bulk', 'fixed', 120.00, '{"minimum_quantity":50}', 3, 1, NOW(), NOW());

CREATE TABLE IF NOT EXISTS bargaining_offers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    design_post_id BIGINT UNSIGNED NOT NULL,
    job_post_application_id BIGINT UNSIGNED NULL,
    parent_offer_id BIGINT UNSIGNED NULL,
    offered_by_user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    estimated_days INT NULL,
    message TEXT NULL,
    status ENUM('pending','accepted','rejected','countered','withdrawn') NOT NULL DEFAULT 'pending',
    responded_by BIGINT UNSIGNED NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bargaining_offers_post (design_post_id),
    CONSTRAINT fk_bargaining_offers_post FOREIGN KEY (design_post_id) REFERENCES design_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_bargaining_offers_application FOREIGN KEY (job_post_application_id) REFERENCES job_post_applications(id) ON DELETE SET NULL,
    CONSTRAINT fk_bargaining_offers_parent FOREIGN KEY (parent_offer_id) REFERENCES bargaining_offers(id) ON DELETE SET NULL,
    CONSTRAINT fk_bargaining_offers_user FOREIGN KEY (offered_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bargaining_offers_responded_by FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NULL,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    min_order_qty INT NOT NULL DEFAULT 1,
    turnaround_days INT NULL,
    is_customizable TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    preview_image_path VARCHAR(255) NULL,
    default_fulfillment_type ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
    automation_profile_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_shop_projects_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_shop_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Production-readiness depth update
ALTER TABLE design_customizations
    ADD COLUMN IF NOT EXISTS design_session_json LONGTEXT NULL AFTER pricing_breakdown_json,
    ADD COLUMN IF NOT EXISTS preview_meta_json LONGTEXT NULL AFTER design_session_json,
    ADD COLUMN IF NOT EXISTS pricing_confidence_score DECIMAL(5,2) NULL AFTER preview_meta_json,
    ADD COLUMN IF NOT EXISTS pricing_strategy VARCHAR(80) NULL AFTER pricing_confidence_score,
    ADD COLUMN IF NOT EXISTS last_priced_at DATETIME NULL AFTER pricing_strategy,
    ADD COLUMN IF NOT EXISTS approved_proof_id BIGINT UNSIGNED NULL AFTER last_priced_at;

ALTER TABLE design_proofs
    ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER responded_at;

ALTER TABLE bargaining_offers
    ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER responded_at,
    ADD COLUMN IF NOT EXISTS negotiation_round INT UNSIGNED NOT NULL DEFAULT 1 AFTER expires_at;

ALTER TABLE shop_projects
    ADD COLUMN IF NOT EXISTS tags_json LONGTEXT NULL AFTER automation_profile_json;

CREATE TABLE IF NOT EXISTS design_customization_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    design_customization_id BIGINT UNSIGNED NOT NULL,
    version_no INT UNSIGNED NOT NULL DEFAULT 1,
    captured_by BIGINT UNSIGNED NULL,
    change_summary VARCHAR(180) NULL,
    snapshot_json LONGTEXT NULL,
    pricing_snapshot_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_design_customization_snapshots_version (design_customization_id, version_no),
    CONSTRAINT fk_design_customization_snapshots_customization FOREIGN KEY (design_customization_id) REFERENCES design_customizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_design_customization_snapshots_actor FOREIGN KEY (captured_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_saved_addresses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_profile_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(60) NOT NULL DEFAULT 'Address',
    recipient_name VARCHAR(150) NULL,
    recipient_phone VARCHAR(30) NULL,
    cavite_location_id BIGINT UNSIGNED NULL,
    address_line TEXT NOT NULL,
    postal_code VARCHAR(20) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    delivery_notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_client_saved_addresses_profile FOREIGN KEY (client_profile_id) REFERENCES client_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_client_saved_addresses_location FOREIGN KEY (cavite_location_id) REFERENCES cavite_locations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operational_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    category VARCHAR(80) NOT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(80) NULL,
    reference_id BIGINT UNSIGNED NULL,
    status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolved_at DATETIME NULL,
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_operational_alerts_shop_status (shop_id, status),
    KEY idx_operational_alerts_reference (reference_type, reference_id),
    CONSTRAINT fk_operational_alerts_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL,
    CONSTRAINT fk_operational_alerts_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_operational_alerts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
