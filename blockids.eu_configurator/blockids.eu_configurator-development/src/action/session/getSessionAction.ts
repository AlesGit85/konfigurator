'use server';

import { getIronSession, IronSession } from 'iron-session';
import { cookies } from 'next/headers';
import { defaultSession, SessionData, sessionOptions } from '@/lib/session';

export async function getSession(parsedCookie?) {
    const session: IronSession<SessionData> = await getIronSession<SessionData>(cookies(), {
        ...sessionOptions,
        cookieOptions: {
            ...sessionOptions.cookieOptions,
            ...(parsedCookie && { maxAge: parsedCookie }), // Expire cookie before the session expires.
        },
    });

    if (!session.isLoggedIn) {
        session.isLoggedIn = defaultSession.isLoggedIn;
        session.fullName = '';
        session.token = '';
    }

    return session;
}
