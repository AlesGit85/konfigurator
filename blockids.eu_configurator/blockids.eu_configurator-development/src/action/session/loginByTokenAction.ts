'use server';

import { IronSession } from 'iron-session';
import { redirect } from 'next/navigation';
import { getSession } from '@/action/session/getSessionAction';
import { validateToken } from '@/app/api/_lib/endpoints';
import { getCookieValue, MAX_AGE } from '@/lib/cookies';
import { SessionData } from '@/lib/session';
import { getEnvRedirectUrlHomeByLocale } from '@/lib/utils';

export async function loginByTokenAction(token: string) {
    const locale = getCookieValue('NEXT_LOCALE') || 'cs';

    const validationResult = await validateToken(locale, token);

    if (validationResult?.data?.id) {
        const accessHash = validationResult?.data?.planInProgress || 'new';
        const session: IronSession<SessionData> = await getSession(MAX_AGE);

        session.isLoggedIn = true;
        session.userId = validationResult.data?.id;
        session.fullName = validationResult.data?.givenName + ' ' + validationResult.data?.familyName;
        session.token = token;
        session.customerTypeId = validationResult.data?.segment.id;
        session.accessHash = accessHash;

        await session.save();

        redirect(`/${locale}/detail/${accessHash}`);
    } else {
        const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlHomeByLocale(locale);
        redirect(WEBSITE_REDIRECT_PATH);
    }
}
