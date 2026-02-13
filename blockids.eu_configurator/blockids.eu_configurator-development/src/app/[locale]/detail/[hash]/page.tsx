import { IronSession } from 'iron-session';
import { redirect } from 'next/navigation';
import { ReactElement } from 'react';
import { getSession } from '@/action/session/getSessionAction';
import PageContainer from '@/app/[locale]/_components/pageContainer/pageContainer';
import { SessionData } from '@/lib/session';
import { getEnvRedirectUrlHomeByLocale } from '@/lib/utils';

export const dynamic = 'force-dynamic';

type PageParamsType = {
    params: { locale: string, hash: string, },
}

export default async function Detail({ params: { locale, hash } }: PageParamsType): Promise<ReactElement> {
    const cookieSession: IronSession<SessionData> = await getSession();

    if (!cookieSession?.isLoggedIn) {
        const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlHomeByLocale(locale);
        redirect(WEBSITE_REDIRECT_PATH);
    }

    const cookieToken = cookieSession.token;

    return (
        <PageContainer
            locale={locale}
            token={cookieToken}
            accessHash={hash}
        />
    );
}
