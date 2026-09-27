import {useEffect} from "react";
import {createPortal} from "react-dom";
import {AnimatePresence,motion} from "framer-motion";
import {FaCheckCircle,FaExclamationCircle,FaTimes} from "react-icons/fa";
import "./Toast.css";

export default function Toast({toast,onClose}){
	useEffect(()=>{
		if(!toast)return;
		const timer=setTimeout(onClose,5000);
		return ()=>clearTimeout(timer);
	},[toast,onClose]);

	return createPortal(
		<div className="toast-viewport">
			<AnimatePresence>
				{toast&&<motion.div
					key={toast.id}
					className={`toast toast-${toast.type}`}
					role={toast.type==="error"?"alert":"status"}
					initial={{opacity:0,y:-16,scale:.95}}
					animate={{opacity:1,y:0,scale:1}}
					exit={{opacity:0,y:-16,scale:.95}}
					transition={{duration:.25}}
				>
					{toast.type==="success"?<FaCheckCircle/>:<FaExclamationCircle/>}
					<p>{toast.message}</p>
					<button type="button" onClick={onClose} aria-label="Fermer la notification"><FaTimes/></button>
				</motion.div>}
			</AnimatePresence>
		</div>,
		document.body
	);
}
