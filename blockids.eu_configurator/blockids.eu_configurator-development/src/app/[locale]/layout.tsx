import clsx from 'clsx';
import { dir } from 'i18next';
import { Poppins } from 'next/font/google';
import { ReactNode } from 'react';
import ReduxProvider from '@/providers/ReduxProvider';
import commonClasses from '@/styles/common.module.scss';
import type { Metadata } from 'next';
import '@/styles/globals.scss';
import ToastContainerWrapper from '@/components/notify/toastContainerWrapper';

const poppins = Poppins({
    subsets: ['latin'],
    weight: ['100', '200', '300', '400', '500', '600', '700', '800', '900'],
});

export async function generateMetadata({ params: { locale } }): Promise<Metadata> {
    const titles = {
        en: 'Configurator | BLOCKids',
        de: 'Konfigurator | BLOCKids',
    };

    return {
        title: titles[locale] || 'Konfigurátor | BLOCKids',
        description: 'BlockIds',
        robots: {
            index: false,
            follow: false,
        },
    };
}

const languages = ['cs'];

export async function generateStaticParams() {
    return languages.map((lng) => ({ lng }));
}

export default function RootLayout(
    {
        children,
        params: { locale },
    }: Readonly<{
        children: ReactNode,
        params: { locale: string, },
    }>,
) {
    return (
        <html
            lang={locale}
            dir={dir(locale)}
        >
            <body
                id={'body'}
                // className={clsx(poppins.className, commonClasses.widths)}
                className={clsx(poppins.className, commonClasses.body)}
            >
                <ReduxProvider>
                    {children}
                </ReduxProvider>
                <ToastContainerWrapper containerId={'global'} />
            </body>
        </html>
    );
}
