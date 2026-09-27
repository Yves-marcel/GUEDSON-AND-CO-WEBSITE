import {useEffect,useRef,useState} from "react";
import {motion,useInView,animate} from "framer-motion";
import {FaHistory,FaMapMarkerAlt,FaUsers,FaGlobeAmericas} from "react-icons/fa";
import {stats} from "../../data/content.js";
import Container from "../common/Container.jsx";
import "./Stats.css";

const icons={history:FaHistory,pin:FaMapMarkerAlt,users:FaUsers,globe:FaGlobeAmericas};

function AnimatedValue({value}){
	const match=value.match(/^(\d+)(.*)$/);
	const target=match?parseInt(match[1],10):0;
	const suffix=match?match[2]:value;
	const ref=useRef(null);
	const inView=useInView(ref,{once:true,amount:.6});
	const [display,setDisplay]=useState(0);
	useEffect(()=>{
		if(!inView) return;
		const controls=animate(0,target,{duration:1.6,ease:[.22,1,.36,1],onUpdate:v=>setDisplay(Math.round(v))});
		return controls.stop;
	},[inView,target]);
	return <span ref={ref}>{display}{suffix}</span>;
}

export default function Stats(){
	return <section className="stats" aria-label="Chiffres clés">
		<Container className="stats-grid">
			{stats.map((item,i)=>{
				const Icon=icons[item.icon];
				return <motion.div
					className="stat-item"
					key={item.label}
					initial={{opacity:0,y:28}}
					whileInView={{opacity:1,y:0}}
					whileHover={{y:-6}}
					viewport={{once:true,amount:.35}}
					transition={{delay:i*.1,duration:.55,type:"spring",stiffness:90}}
				>
					<div className="stat-icon"><Icon/></div>
					<strong><AnimatedValue value={item.value}/></strong>
					<span className="stat-label">{item.label}</span>
				</motion.div>
			})}
		</Container>
	</section>
}
