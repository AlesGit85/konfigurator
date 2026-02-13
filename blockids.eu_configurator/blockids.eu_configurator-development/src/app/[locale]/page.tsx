import { redirect } from 'next/navigation';
import { ReactElement } from 'react';
import { getSession } from '@/action/session/getSessionAction';
import { getEnvRedirectUrlHomeByLocale } from '@/lib/utils';

export const dynamic = 'force-dynamic';

type PageParamsType = {
    params: { locale: string, },
}

export default async function Home({ params: { locale } }: PageParamsType): Promise<ReactElement> {
    const cookieSession = await getSession();

    if (!cookieSession?.isLoggedIn) {
        const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlHomeByLocale(locale);
        redirect(WEBSITE_REDIRECT_PATH);
    } else {
        redirect(`/${locale}/detail/${cookieSession.accessHash}`);
    }
}
