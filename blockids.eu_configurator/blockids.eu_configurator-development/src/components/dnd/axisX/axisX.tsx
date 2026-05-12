import clsx from 'clsx';
import { memo } from 'react';
import classes from '@/components/dnd/axisX/axisX.module.scss';

export interface IAxisXProps {
    className: string
    source: string[]
    base: number
}

const AxisX = (
    {
        className,
        source,
        base,
    }: IAxisXProps,
) => {
    return (
        <div className={clsx(classes.root, className)}>
            <span
                className={clsx(classes.value, classes.zero)}
                data-size={0}
            />
            {source.map((item, index) => (
                <span
                    className={classes.value}
                    key={item}
                    data-size={base * (index + 1)}
                >
                    {item}
                </span>
            ))}
        </div>
    );
};

export default memo(AxisX);
