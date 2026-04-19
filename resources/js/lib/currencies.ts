export interface Currency {
    code: string;
    symbol: string;
}

export const CURRENCIES: Currency[] = [
    { code: 'GBP', symbol: '£'  },
    { code: 'EUR', symbol: '€'  },
    { code: 'USD', symbol: '$'  },
    { code: 'CAD', symbol: '$'  },
    { code: 'AUD', symbol: '$'  },
    { code: 'NZD', symbol: '$'  },
    { code: 'CHF', symbol: 'Fr' },
    { code: 'NOK', symbol: 'kr' },
    { code: 'SEK', symbol: 'kr' },
    { code: 'DKK', symbol: 'kr' },
    { code: 'JPY', symbol: '¥'  },
    { code: 'CNY', symbol: '¥'  },
    { code: 'INR', symbol: '₹'  },
    { code: 'SGD', symbol: '$'  },
    { code: 'HKD', symbol: '$'  },
    { code: 'AED', symbol: 'AED'},
    { code: 'SAR', symbol: 'SAR'},
    { code: 'ZAR', symbol: 'R'  },
    { code: 'BRL', symbol: 'R$' },
    { code: 'MXN', symbol: '$'  },
    { code: 'PLN', symbol: 'zł' },
    { code: 'TRY', symbol: '₺'  },
    { code: 'RON', symbol: 'lei'},
    { code: 'CZK', symbol: 'Kč' },
    { code: 'HUF', symbol: 'Ft' },
    { code: 'KRW', symbol: '₩'  },
    { code: 'THB', symbol: '฿'  },
    { code: 'MYR', symbol: 'RM' },
    { code: 'PHP', symbol: '₱'  },
    { code: 'IDR', symbol: 'Rp' },
];

const SYMBOL_MAP: Record<string, string> = Object.fromEntries(
    CURRENCIES.map(c => [c.code, c.symbol]),
);

/** Returns the currency symbol for a given ISO 4217 code, falling back to the code itself. */
export function currencySymbol(code: string): string {
    return SYMBOL_MAP[code] ?? code;
}

/** Formats a price string with the currency symbol prepended. */
export function formatPrice(price: string | null | undefined, currencyCode = 'GBP'): string {
    if (!price) return '';
    return `${currencySymbol(currencyCode)}${price}`;
}
