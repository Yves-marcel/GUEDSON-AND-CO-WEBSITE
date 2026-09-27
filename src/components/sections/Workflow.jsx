import {motion} from "framer-motion";
import Section from "../common/Section.jsx";
import SectionTitle from "../common/SectionTitle.jsx";
import {steps} from "../../data/content.js";
import workflowImage from "../../assets/images/4.jpg";
import "./Workflow.css";

export default function Workflow(){
	return <Section className="workflow-section">
		<div className="workflow-columns">
			<div className="workflow-intro">
				<SectionTitle center={false} eyebrow="COMMENT NOUS TRAVAILLONS" title="Un parcours logistique maîtrisé" description="De l'analyse de votre besoin jusqu'à la livraison, nous coordonnons chaque étape avec méthode, précision et transparence."/>
				<img className="workflow-intro-image" src={workflowImage} alt="Opération de logistique internationale" loading="lazy" />
			</div>
			<div className="workflow-cards">
			{steps.map(([title,description],i)=><motion.article
				className={`workflow-card workflow-card--${i+1}`}
				key={title}
				initial={{opacity:0,y:24}}
				whileInView={{opacity:1,y:0}}
				viewport={{once:true,amount:.2}}
				transition={{duration:.5,delay:i*.08,ease:[.22,1,.36,1]}}
			>
				<b>{String(i+1).padStart(2,"0")}</b>
				<div><h3>{title}</h3><p>{description}</p></div>
				<span className="workflow-card-arrow" aria-hidden="true">&#8594;</span>
			</motion.article>)}
			</div>
		</div>
	</Section>
}