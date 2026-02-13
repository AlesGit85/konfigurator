import { redirect } from 'next/navigation';
import { ReactElement } from 'react';
import SignIn from '@/app/[locale]/sso/_components/signIn';
import initTranslations from '@/app/i18n';
import { getEnvRedirectUrlHomeByLocale } from '@/lib/utils';
import TranslationsProvider from '@/providers/TranslationsProvider';

export const dynamic = 'force-dynamic';

type PageParamsType = {
    params: { locale: string, },
    searchParams?: { [key: string]: string | undefined, },
}

const i18nNamespaces = ['common', 'header'];

export default async function Login({ params: { locale }, searchParams }: PageParamsType): Promise<ReactElement> {
    const { resources } = await initTranslations(locale, i18nNamespaces);

    let component: JSX.Element = <div>Error</div>;

    const tokenQueryValue: string | undefined = searchParams?.t;

    if (tokenQueryValue) {
        component = (
            <SignIn
                token={tokenQueryValue}
            />
        );
    } else {
        const WEBSITE_REDIRECT_PATH = getEnvRedirectUrlHomeByLocale(locale);
        redirect(WEBSITE_REDIRECT_PATH);
    }

    return (
        <TranslationsProvider
            locale={locale}
            namespaces={i18nNamespaces}
            resources={resources}
        >
            {component}
        </TranslationsProvider>

    );
}
