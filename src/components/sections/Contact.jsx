import {useState} from "react";import {FaMapMarkerAlt,FaPhone,FaEnvelope,FaClock,FaWhatsapp} from "react-icons/fa";import Section from "../common/Section.jsx";import SectionTitle from "../common/SectionTitle.jsx";import Toast from "../common/Toast.jsx";import "./Contact.css";
const details=[[FaMapMarkerAlt,"Localisation","Grand-Bassam, Côte d'Ivoire"],[FaEnvelope,"Email","sales@guedsonandcointertrade.com","mailto:sales@guedsonandcointertrade.com"],[FaPhone,"Téléphone",null,null,[["Secrétariat : (+225) 05 54 05 38 36","+2250554053836"],["Commercial : (+225) 07 99 50 60 41","+2250799506041"]]],[FaClock,"Horaires","Lundi – Vendredi : 8h00 – 18h00"]];
const EMAIL_RE=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const MAX_LENGTH={name:100,email:120,subject:150,message:2000};
const SINGLE_LINE_FIELDS=new Set(["name","email","subject"]);
const API_URL=import.meta.env.VITE_API_URL||"http://localhost:8000";
// Retire les caractères de contrôle (retours à la ligne, tabulations, octets nuls...) qui n'ont
// rien à faire dans un champ mono-ligne et pourraient servir à une injection d'en-tête e-mail.
const stripControlChars=value=>value.replace(/[\r\n\t\0\x0B\x0C]/g,"");
export default function Contact(){
	const [form,setForm]=useState({name:"",email:"",subject:"",message:"",company:""});
	const [toast,setToast]=useState(null);
	const [sending,setSending]=useState(false);
	const notify=(type,message)=>setToast({id:Date.now(),type,message});
	const updateField=event=>{
		const {name,value}=event.target;
		const limit=MAX_LENGTH[name];
		const cleaned=SINGLE_LINE_FIELDS.has(name)?stripControlChars(value):value;
		setForm(f=>({...f,[name]:limit?cleaned.slice(0,limit):cleaned}));
	};
	const getMessage=()=>`Bonjour,\n\nNom : ${form.name.trim()}\nE-mail : ${form.email.trim()}\nObjet : ${form.subject.trim()}\n\nMessage :\n${form.message.trim()}`;
	const validate=()=>{
		if(form.company.trim())return false;
		if(!form.name.trim()||!form.email.trim()||!form.subject.trim()||!form.message.trim()){
			notify("error","Merci de renseigner tous les champs.");
			return false;
		}
		if(!EMAIL_RE.test(form.email.trim())){
			notify("error","Merci de renseigner une adresse e-mail valide.");
			return false;
		}
		return true;
	};
	const sendByEmail=async()=>{
		if(!validate())return;
		setSending(true);
		try{
			const response=await fetch(`${API_URL}/contact.php`,{
				method:"POST",
				headers:{"Content-Type":"application/json"},
				body:JSON.stringify(form),
			});
			const data=await response.json();
			if(!response.ok||!data.success){
				notify("error",data.message||"Une erreur est survenue. Merci de réessayer.");
				return;
			}
			notify("success",data.message||"Votre message a bien été envoyé.");
			setForm({name:"",email:"",subject:"",message:"",company:""});
		}catch{
			notify("error","Impossible de contacter le serveur. Merci de réessayer plus tard.");
		}finally{
			setSending(false);
		}
	};
	const sendByWhatsApp=()=>{
		if(!validate())return;
		window.open(`https://wa.me/2250799506041?text=${encodeURIComponent(getMessage())}`,"_blank","noopener,noreferrer");
	};
	return <Section id="contact"><SectionTitle eyebrow="CONTACT" title="Parlons de votre projet" description="Notre équipe reste disponible pour vous accompagner dans vos opérations de transit et de logistique."/><div className="contact-layout"><div className="contact-details">{details.map(([,title,text,href,phones])=><div className="contact-item" key={title}><h3>{title}</h3><p>{href?<a href={href}>{text}</a>:phones?phones.map(([label,number])=><span key={number}><a href={`tel:${number}`}>{label}</a></span>):text}</p></div>)}</div><form className="contact-form" onSubmit={event=>event.preventDefault()} noValidate><div className="form-fields"><label>Nom<input name="name" value={form.name} onChange={updateField} maxLength={MAX_LENGTH.name} autoComplete="name" required/></label><label>E-mail<input type="email" name="email" value={form.email} onChange={updateField} maxLength={MAX_LENGTH.email} autoComplete="email" required/></label></div><label>Objet<input name="subject" value={form.subject} onChange={updateField} maxLength={MAX_LENGTH.subject} required/></label><label>Message<textarea name="message" value={form.message} onChange={updateField} maxLength={MAX_LENGTH.message} rows="5" required/></label><input type="text" name="company" value={form.company} onChange={updateField} className="hp-field" tabIndex="-1" autoComplete="off" aria-hidden="true"/><div className="form-actions"><button type="submit" onClick={sendByEmail} disabled={sending}>{sending?"Envoi en cours…":"Envoyer par e-mail"}</button><button type="submit" className="whatsapp-submit" onClick={sendByWhatsApp}><FaWhatsapp/> Envoyer par WhatsApp</button></div></form></div><Toast toast={toast} onClose={()=>setToast(null)}/></Section>
}
