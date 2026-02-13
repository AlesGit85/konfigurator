'use server';

// import { getSession } from '@/action/session/getSessionAction';

interface FetchApiConfig {
    method?: string
    lang?: string
    body?: {}
    tags?: string[]
    customHeaders?: HeadersInit
    isPrivate?: boolean
    isCustomBody?: boolean
}

interface FetchApiResponse {
    status: number
    [key: string]: any
}

export async function fetchApi(
    endpoint: string,
    config: FetchApiConfig = {},
): Promise<FetchApiResponse> {
    // const cookieSession = await getSession();
    const {
        method = 'GET',
        body = {},
        tags = [],
        customHeaders = {},
        isPrivate = false,
        isCustomBody = false,
    } = config;

    const apiType = process.env.API_BASE_VERSION;

    const requestBody = Object.keys(body).length ? JSON.stringify(body) : null;

    const headers = {
        Accept: 'application/ld+json',
        'Content-Type': 'application/ld+json',
        // ...(cookieSession?.token && {
        //     'Cookie': `BEARER=${cookieSession?.token}`,
        // }),
        ...customHeaders,
    };

    const requestOptions = {
        method: method,
        headers: headers,
        next: {
            tags: tags,
        },
    };

    if (requestBody) {
        requestOptions.body = requestBody;
    }

    if (isCustomBody) {
        requestOptions.body = body;
    }

    const res = await fetch(
        `${process.env.API_BASE_PATH}${apiType}${endpoint}`,
        requestOptions,
    );

    if (!res.ok) {
        console.log(res.status, endpoint);
    }

    return {
        status: res.status,
        data: await res.json(),
    };
}

export async function fetchInternalApi(
    endpoint: string,
    config: FetchApiConfig = {},
) {
    const {
        method = 'GET',
        lang = 'cs_CZ',
        body = {},
        isCustomBody = false,
    } = config;

    const requestBody = Object.keys(body).length ? JSON.stringify(body) : null;

    const headers = {
        Accept: 'application/ld+json',
        'Content-Language': lang,
    };

    const requestOptions = {
        method: method,
        headers: headers,
        next: {
            tags: tags,
        },
    };

    if (requestBody) {
        requestOptions.body = requestBody;
    }

    if (isCustomBody) {
        requestOptions.body = body;
    }

    const res = await fetch(
        `api/${endpoint}`,
        requestOptions,
    );

    if (!res.ok) {
        console.log(res.status, endpoint);
    }

    return {
        status: res.status,
        data: {
            ...await res.json(),
        },
    };
}
