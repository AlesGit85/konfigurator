'use server';

import { IronSession } from 'iron-session';
import { getSession } from '@/action/session/getSessionAction';
import { deleteDraft } from '@/app/api/_lib/endpoints';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function deleteDraftAction(accessHash: string): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR010');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';

    try {
        await deleteDraft(locale, cookieSession.token, accessHash);
    } catch (e) {
        throw new Error('ERR011');
    }
}
