'use server';

import { IronSession } from 'iron-session';
import { redirect } from 'next/navigation';
import { getSession } from '@/action/session/getSessionAction';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import { confirmDraft, saveDraft, SaveDraftPayloadType } from '@/app/api/_lib/endpoints';
import * as CONST from '@/lib/constants';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';
import { getEnvRedirectUrlByLocale } from '@/lib/utils';

export async function confirmDraftAction(payload: SaveDraftPayloadType): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR005');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';
    const token = cookieSession?.token;
    const accessHash = cookieSession?.accessHash;

    const saveDraftResult = await saveDraft(locale, token, accessHash, payload);

    if (!saveDraftResult) {
        throw new Error('ERR012');
    }

    const confirmDraftResult = await confirmDraft(locale, token, accessHash, payload);

    const responseAccessHash = confirmDraftResult?.data?.accessHash;

    if (!responseAccessHash) {
        throw new Error('ERR006');
    } else {
        const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlByLocale(locale);

        await updateSessionAction(CONST.DRAFT_ID_HASH, responseAccessHash);
        redirect(WEBSITE_REDIRECT_PATH);
    }
}
