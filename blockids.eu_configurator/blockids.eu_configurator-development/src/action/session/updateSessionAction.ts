'use server';

import { IronSession } from 'iron-session';
import { getSession } from '@/action/session/getSessionAction';
import { MAX_AGE } from '@/lib/cookies';
import { SessionData } from '@/lib/session';

export async function updateSessionAction(param: string, value: string) {
    const session: IronSession<SessionData> = await getSession(MAX_AGE);
    session[param] = value;
    await session.save();
}
