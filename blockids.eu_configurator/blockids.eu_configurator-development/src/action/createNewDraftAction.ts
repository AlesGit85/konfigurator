'use server';

import { IronSession } from 'iron-session';
import { revalidateTag } from 'next/cache';
import { redirect } from 'next/navigation';
import { getSession } from '@/action/session/getSessionAction';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import { CreateDraftPayloadType, createNewDraft } from '@/app/api/_lib/endpoints';
import * as CONST from '@/lib/constants';
import { getCookieValue } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function createNewDraftAction(payload: CreateDraftPayloadType): Promise<void> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR002');
    }

    const locale = getCookieValue('NEXT_LOCALE') || 'cs';
    const newDraftResult = await createNewDraft(locale, cookieSession.token, payload);

    const responseAccessHash = newDraftResult?.data?.accessHash;

    if (responseAccessHash) {
        revalidateTag('detail');
        await updateSessionAction(CONST.DRAFT_ID_HASH, responseAccessHash);
        redirect(`/${locale}/detail/${responseAccessHash}`);
    } else {
        throw new Error('ERR001');
    }
}
