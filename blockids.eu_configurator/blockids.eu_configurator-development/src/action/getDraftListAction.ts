'use server';

import { IronSession } from 'iron-session';
import { getSession } from '@/action/session/getSessionAction';
import { getMyDraftList } from '@/app/api/_lib/endpoints';
import { SessionData } from '@/lib/session';
import { DraftListItemType } from '@/redux/types/configuratorTypes';

export async function getDraftListAction(): Promise<DraftListItemType[]> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        throw new Error('ERR007');
    }

    const token: string = cookieSession?.token;
    return await getMyDraftList(token);
}
