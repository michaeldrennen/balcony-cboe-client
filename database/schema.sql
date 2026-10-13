CREATE TABLE IF NOT EXISTS balcony_cboe_volatility_observations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticker VARCHAR(16) NOT NULL,
    observed_at DATE NOT NULL,
    open DECIMAL(8,4) NOT NULL,
    high DECIMAL(8,4) NOT NULL,
    low DECIMAL(8,4) NOT NULL,
    close DECIMAL(8,4) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY balcony_cboe_ticker_observed_unique (ticker, observed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
