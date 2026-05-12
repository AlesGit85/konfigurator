import { ReactElement } from 'react';
import PageContainer from '@/app/[locale]/_components/pageContainer/pageContainer';

export const dynamic = 'force-dynamic';

type PageParamsType = {
    params: { locale: string, },
    searchParams: { id: string, t: string, },
}

export default async function View({ params: { locale }, searchParams }: PageParamsType): Promise<ReactElement> {
    const hash = searchParams?.id;
    const cookieToken = searchParams?.t;

    const isDraftReadOnly = true;

    return (
        <PageContainer
            locale={locale}
            token={cookieToken}
            accessHash={hash}
            isDraftReadOnly={isDraftReadOnly}
        />
    );
}
