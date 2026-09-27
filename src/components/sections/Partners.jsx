import Section from "../common/Section.jsx";
import SectionTitle from "../common/SectionTitle.jsx";
import "./Partners.css";
import partner1 from "../../assets/images/partenaire-1.jpg";
import partner2 from "../../assets/images/partenaire-2.jpg";
import partner3 from "../../assets/images/partenaire-3.jpg";
import partner4 from "../../assets/images/partenaire-4.jpg";
import partner5 from "../../assets/images/partenaire-5.jpg";
import partner6 from "../../assets/images/partenaire-6.jpg";
import partner7 from "../../assets/images/partenaire-7.jpg";
import partner8 from "../../assets/images/partenaire-8.jpg";
import partner9 from "../../assets/images/partenaire-9.jpg";
import partner10 from "../../assets/images/partenaire-10.jpg";
import partner11 from "../../assets/images/partenaire-11.jpg";

const partners=[partner1,partner2,partner3,partner4,partner5,partner6,partner7,partner8,partner9,partner10,partner11];

export default function Partners(){return <Section id="partners" className="partners-section"><SectionTitle eyebrow="NOS PARTENAIRES" title="Des partenaires qui font avancer vos projets" description="Un écosystème fiable pour vous accompagner au-delà des frontières."/><div className="partners-grid">{partners.map((partner,index)=><div className="partner-tile" key={partner}><img src={partner} alt={`Partenaire ${index+1}`} loading="lazy"/></div>)}</div></Section>}
