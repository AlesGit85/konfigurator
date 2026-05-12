import clsx from 'clsx';
import { memo } from 'react';
import classes from '@/components/dnd/axisY/axisY.module.scss';

export interface IAxisXProps {
    className: string
    source: number[]
    base: number
}

const AxisY = (
    {
        className,
        source,
        base,
    }: IAxisXProps,
) => {
    return (
        <div className={clsx(classes.root, className)}>
            {source.map((item, index) => (
                <span
                    className={classes.value}
                    key={item}
                    data-size={base * (index + 1)}
                >
                    {item}
                </span>
            )).toReversed()}
            <span
                className={clsx(classes.value, classes.zero)}
                data-size={0}
            />
        </div>
    );
};

export default memo(AxisY);
