import Header from "./components/Header";
import "../styles/Copilot.css";


export const Copilot = () =>{


    return(
        <>
        <Header />
        <section className="copilot-hero">
            <div className="copilot-content">
                <h1>What’s On Your Mind</h1>

                <div className="copilot-input-wrapper">
                <input
                    type="text"
                    placeholder="Tell us what you want to build"
                    aria-label="What do you want to build"
                />
                </div>
            </div>
        </section>
        </>
    );
}