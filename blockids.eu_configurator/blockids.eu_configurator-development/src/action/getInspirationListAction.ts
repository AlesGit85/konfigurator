'use server';

import { getInspirationList } from '@/app/api/_lib/endpoints';

export async function getInspirationListAction(locale: string) {
    const inspirationResponse = await getInspirationList(locale);
    return inspirationResponse?.data || [];
}
