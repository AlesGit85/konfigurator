'use server';

import { IronSession } from 'iron-session';
import { redirect } from 'next/navigation';
import { getSession } from '@/action/session/getSessionAction';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import { cloneDraft } from '@/app/api/_lib/endpoints';
import * as CONST from '@/lib/constants';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function copyDraftAction(accessHash: string): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR008');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';
    const copiedDraftResult = await cloneDraft(locale, cookieSession.token, accessHash);

    const responseAccessHash = copiedDraftResult?.data?.accessHash;

    if (responseAccessHash) {
        await updateSessionAction(CONST.DRAFT_ID_HASH, responseAccessHash);
        redirect(`/${locale}/detail/${responseAccessHash}`);
    } else {
        throw new Error('ERR009');
    }
}
