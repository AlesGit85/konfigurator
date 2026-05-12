'use server';

import { IronSession } from 'iron-session';
import { getSession } from '@/action/session/getSessionAction';
import { changeDraftTitle } from '@/app/api/_lib/endpoints';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function changeTitleAction(title: string): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('title-change');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';
    await changeDraftTitle(locale, cookieSession.token, cookieSession.accessHash, title);
}
