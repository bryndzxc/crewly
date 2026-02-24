export default function ApplicationLogo({ className, ...props }) {
    return (
        <img
            {...props}
            src="/storage-images/crewly_logo.png"
            alt="Crewly"
            className={className}
            loading="eager"
            decoding="async"
        />
    );
}
