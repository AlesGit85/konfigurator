import { fetchApi } from '@/lib/fetcher';

export async function getCustomerMe(token: string) {
    return await fetchApi(`/customers/me/${token}`, {
        tags: ['me'],
    });
}

export async function validateToken(lang: string, token: string) {
    return await getCustomerMe(token);
}

export async function getMyDraftList(token: string) {
    try {
        const response = await getCustomerMe(token);
        return response?.data.plans;
    } catch (e) {
        return [];
    }
}

export async function getDeskList(lang: string) {
    return await fetchApi(`/desks/${lang}`, {
        tags: ['desks'],
    });
}

export async function getHoldList(lang: string) {
    return await fetchApi(`/grips/${lang}`, {
        tags: ['grips'],
    });
}

export async function getMattressList(lang: string) {
    return await fetchApi(`/mattresses/${lang}`, {
        tags: ['mattresses'],
    });
}

export async function getInspirationList(lang: string) {
    return await fetchApi(`/photos/${lang}`, {
        tags: ['inspiration'],
    });
}

export async function getFaqList(lang: string) {
    return await fetchApi(`/faq-items/${lang}`, {
        tags: ['faq'],
    });
}

export async function createNewDraft(lang: string, token: string, payload: CreateDraftPayloadType) {
    return await fetchApi(`/plans/create/${lang}/${token}`, {
        method: 'POST',
        body: payload,
    });
}

export async function saveDraft(lang: string, token: string, hash: string, payload: SaveDraftPayloadType) {
    return await fetchApi(`/plans/update/${lang}/${token}/${hash}`, {
        method: 'POST',
        body: payload,
    });
}

export async function confirmDraft(lang: string, token: string, hash: string, payload: SaveDraftPayloadType) {
    return await fetchApi(`/plans/confirm/${lang}/${token}/${hash}`, {
        method: 'POST',
        body: payload,
    });
}

export async function deleteDraft(lang: string, token: string, hash: string) {
    return await fetchApi(`/plans/delete/${lang}/${token}/${hash}`, {
        method: 'DELETE',
    });
}

export async function cloneDraft(lang: string, token: string, hash: string) {
    return await fetchApi(`/plans/clone/${lang}/${token}/${hash}`, {
        method: 'POST',
    });
}

export async function changeDraftTitle(lang: string, token: string, hash: string, title: string) {
    return await fetchApi(`/plans/change-title/${lang}/${token}/${hash}`, {
        method: 'POST',
        body: {
            title,
        },
    });
}

export async function getDraftDetail(lang: string, token: string, hash: string) {
    return await fetchApi(`/plans/detail/${lang}/${token}/${hash}`, {
        tags: ['detail'],
    } );
}

export type SaveDraftPayloadType = {
    orientation: string,
    calculatedWidth: number,
    calculatedHeight: number,
    customWidth: number,
    customHeight: number,
    grip: number,
    mattress: number,
    mattressQuantity: number,
    gripQuantity: number,
    workspace: {
        [key: string]: string | object,
    },
}

export type CreateDraftPayloadType = {
    location: string,
}
