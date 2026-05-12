import { useMemo } from 'react';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import { DraftControlAllowedTypes, GridTemplateType } from '@/redux/types/configuratorTypes';

// TODO define languages

export function getUFullLang(lang: string): string {
    switch (lang) {
        case 'cs':
            return 'cs_CZ';
        case 'en':
            return 'en_US';
        case 'pl':
            return 'pl_PL';
        case 'sk':
            return 'sk_SK';
        default:
            return lang;
    }
}

export function getCustomerType(id: number | undefined): string {
    switch (id) {
        case 1:
            return CONST.CUSTOMER_TYPE_FAMILY;
        case 2:
            return CONST.CUSTOMER_TYPE_PUBLIC;
        default:
            return CONST.CUSTOMER_TYPE_FAMILY;
    }
}

export function getDirection(apiDirection: string): string {
    switch (apiDirection) {
        case 'horizontal':
            return CONST.GRID_ALIGNMENT_HORIZONTAL;
        case 'vertical':
            return CONST.GRID_ALIGNMENT_VERTICAL;
        default:
            return CONST.GRID_ALIGNMENT_VERTICAL;
    }
}

/**
 * Calculate mattress count
 * @param direction
 * @param deskHorizontalCount
 * @param isPersonal
 * @param isIndividualSize
 */
export function getMattressCount({
    direction,
    deskHorizontalCount,
    isPersonal,
    isIndividualSize,
}: {
    direction: GridDirectionType,
    deskHorizontalCount: number,
    isPersonal: boolean,
    isIndividualSize: boolean,
}): number {
    let count: number = 0;
    let rowDeskCount: number = 0;

    if (isPersonal && direction === CONST.GRID_ALIGNMENT_VERTICAL) {
        return deskHorizontalCount;
    }

    const deskArray = [...Array(deskHorizontalCount)];

    for (const _ of deskArray) {
        if (isPersonal) {
            if (direction === CONST.GRID_ALIGNMENT_HORIZONTAL) {
                if (!rowDeskCount) {
                    if (!isIndividualSize) {
                        count += 2;
                    } else {
                        count += 1;
                    }
                    rowDeskCount += 1;
                    continue;
                }

                if (isIndividualSize) {
                    if ((rowDeskCount + 1) % 2 === 0) {
                        count += 2;
                    } else {
                        count += 1;
                    }
                } else {
                    count += (rowDeskCount % 2 === 0) ? 2 : 1;
                }
                rowDeskCount += 1;
            }
        } else {
            count += 1;
        }
    }
    return count;
}

/**
 * Calculate hold/grips count
 * @param configuratorGridTemplate
 */
export const getHoldCount = (configuratorGridTemplate: GridTemplateType) => {
    let count: number = 0;
    for (const cell of Object.keys(configuratorGridTemplate)) {
        const gridCell = configuratorGridTemplate[cell];

        if (gridCell) {
            const deskType: DraftControlAllowedTypes = getDeskType(gridCell.desk.type);
            if (deskType === CONST.DESK_TYPE_TRIANGLE) {
                count += 1 / 4;
            }

            if (deskType === CONST.DESK_TYPE_RECTANGLE) {
                count += 1 / 2;
            }
        }
    }
    return Math.ceil(count);
};

export const getRandomNumber = (max: number, min: number = 1) => Math.round(Math.random() * (max - min) + min);

/**
 * Translate desk type
 * @param type
 */
export const getDeskType = (type: string): DraftControlAllowedTypes => {
    if (type?.includes('triangle')) {
        return CONST.DESK_TYPE_TRIANGLE;
    }
    if (type?.includes('table')) {
        return CONST.DESK_TYPE_BLACKBOARD;
    }
    return type as DraftControlAllowedTypes;
};

export const getEnvRedirectUrlByLocale = (locale: string) => {
    return process.env[`NEXT_PUBLIC_URL_REDIRECT_PATH_${locale?.toUpperCase()}`] || '';
};

export const getEnvRedirectUrlHomeByLocale = (locale: string) => {
    const url = new URL(process.env[`NEXT_PUBLIC_URL_REDIRECT_PATH_${locale?.toUpperCase()}`] || '');
    url.pathname = '/';

    return url.toString();
};

/**
 * Convert cm back to Pixels
 * @param cm
 * @param multiplier
 */
export const convert = (cm: number, multiplier: number) => {
    return Math.round(Number(cm * multiplier));
};

export const deepEqual = (x, y): boolean => {
    const ok = Object.keys; const tx = typeof x; const ty = typeof y;
    return x && y && tx === 'object' && tx === ty ? (
        ok(x).length === ok(y).length &&
        ok(x).every(key => deepEqual(x[key], y[key]))
    ) : (x === y);
};
