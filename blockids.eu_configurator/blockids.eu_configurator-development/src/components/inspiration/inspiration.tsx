'use client';

import Image from 'next/image';
import React, { memo, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { getInspirationListAction } from '@/action/getInspirationListAction';
import classes from './inspiration.module.scss';
import Fancybox from '@/components/fancybox/fancybox';

const Inspiration = () => {
    const { t, i18n: { language: locale } } = useTranslation();

    const [inspirationSource, setInspirationSource] = useState([]);

    useEffect(() => {
        if (!!inspirationSource.length) return;
        const loadInspiration = async() => {
            const payload = await getInspirationListAction(locale);
            setInspirationSource(payload);
        };
        loadInspiration();
    }, [inspirationSource]);

    return (
        <div className={classes.root}>
            <div className={classes.section}>
                <div className={classes.title}>{t('inspiration:inspirationTitle')}</div>

                <Fancybox
                    options={{
                        Carousel: {
                            infinite: false,
                        },
                    }}
                >
                    <div className={classes.content}>
                        {inspirationSource.map((photo: { id: number, oder: number, image: string, }) => {
                            return (
                                <a
                                    data-fancybox="gallery"
                                    href={photo?.image}
                                    key={photo?.id}
                                    className={classes.wrapper}
                                >
                                    <Image
                                        className={classes.image}
                                        src={photo?.image}
                                        alt={''}
                                        width={100}
                                        height={60}
                                        priority={true}
                                    />
                                </a>
                            );
                        })}
                    </div>
                </Fancybox>
            </div>
        </div>
    );
};

export default memo(Inspiration);
