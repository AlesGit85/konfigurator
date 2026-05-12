'use server';

import { IronSession } from 'iron-session';
import { redirect } from 'next/navigation';
import { getSession } from '@/action/session/getSessionAction';
import { getCookieValue, MAX_AGE } from '@/lib/cookies';
import { SessionData } from '@/lib/session';
import { getEnvRedirectUrlHomeByLocale } from '@/lib/utils';

export async function logoutAction() {
    const locale = getCookieValue('NEXT_LOCALE') || 'cs';

    const session: IronSession<SessionData> = await getSession(MAX_AGE);
    session.destroy();

    const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlHomeByLocale(locale);
    redirect(WEBSITE_REDIRECT_PATH);
}
