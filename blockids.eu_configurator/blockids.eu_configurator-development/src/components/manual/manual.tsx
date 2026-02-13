'use client';

import { memo, useMemo } from 'react';
// @ts-ignore
import Faq from 'react-faq-component';
import { useTranslation } from 'react-i18next';
import QuestionSvg from '/public/icons/question.svg';
import classes from './manual.module.scss';

export type IManualFaq = {
    question: string,
    answer: string,
}

export interface IManual {
    initialData: IManualFaq[]
}

const Manual = (
    {
        initialData,
    }: IManual,
) => {
    const { t } = useTranslation();

    const faqRows = initialData?.map((item: { question: string, answer: string, }) => ({
        title: item.question,
        content: item.answer,
    }));

    const styles = useMemo(() => ({
        titleTextColor: 'blue',
        rowTitleColor: '#292929',
        rowTitleTextSize: '1rem',
        rowContentColor: '#292929',
        rowContentTextSize: '.875rem',
    }), []);

    return (
        <div className={classes.root}>
            <div className={classes.section}>
                <div className={classes.title}>{t('manual:manualTitle')}</div>
                <div className={classes.content}>
                    <div className={classes.description}>{t('manual:manualDescription')}</div>
                    <div className={classes.faq}>
                        <Faq
                            data={{
                                rows: faqRows,
                            }}
                            styles={styles}
                            config={{
                                animate: true,
                                arrowIcon: ' ',
                                tabFocus: true,
                            }}
                        />
                    </div>
                    <div className={classes.contact}>
                        <div className={classes.contactTitle}>
                            <span className={classes.icon}><QuestionSvg/></span>
                            <span>{t('manual:manualContactTitle')}</span>
                        </div>
                        <div className={classes.contactDescription}>{t('manual:manualContactDescription')}</div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default memo(Manual);
