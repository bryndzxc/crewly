export default function ApplicationLogo({ className, ...props }) {
    return (
        <img
            {...props}
            src="/favicon.png"
            alt="Crewly"
            className={className}
            loading="eager"
            decoding="async"
        />
    );
}
