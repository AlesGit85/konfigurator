'use server';

import { IronSession } from 'iron-session';
import { getSession } from '@/action/session/getSessionAction';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import { saveDraft, SaveDraftPayloadType } from '@/app/api/_lib/endpoints';
import * as CONST from '@/lib/constants';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function saveDraftAction(payload: SaveDraftPayloadType): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR003');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';
    const token = cookieSession?.token;
    const accessHash = cookieSession?.accessHash;

    const saveDraftResult = await saveDraft(locale, token, accessHash, payload);

    const responseAccessHash = saveDraftResult?.data?.accessHash;

    if (!responseAccessHash) {
        throw new Error('ERR004');
    } else {
        // await updateSessionAction(CONST.DRAFT_ID_HASH, responseAccessHash);
    }
}
