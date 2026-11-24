from pathlib import Path
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, HRFlowable

root = Path(__file__).resolve().parent.parent
output = root / "output" / "pdf" / "curriculo-demonstracao.pdf"
output.parent.mkdir(parents=True, exist_ok=True)
styles = getSampleStyleSheet()
styles.add(ParagraphStyle("CVTitle", fontName="Helvetica-Bold", fontSize=23, leading=28, textColor=colors.HexColor("#132132"), spaceAfter=10))
styles.add(ParagraphStyle("CVSection", fontName="Helvetica-Bold", fontSize=12, leading=18, spaceBefore=22, spaceAfter=8, textColor=colors.HexColor("#b82e68")))
styles.add(ParagraphStyle("CVBody", fontName="Helvetica", fontSize=11, leading=18, spaceAfter=8, alignment=TA_LEFT))

story = []
def paragraph(text, style="CVBody"):
    story.append(Paragraph(text, styles[style]))

paragraph("Candidato de demonstração", "CVTitle")
paragraph("PERFIL FICTÍCIO - somente para testes do Point Carreiras")
story.append(Spacer(1, 8))
story.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor("#69aee0")))
paragraph("Resumo profissional", "CVSection")
paragraph("Desenvolvedor PHP com 2 anos de experiência em Laravel, de janeiro de 2024 a dezembro de 2025. Atua no desenvolvimento de aplicações web, APIs REST e testes automatizados com PHPUnit.")
paragraph("Experiência profissional", "CVSection")
paragraph("<b>Desenvolvedor Web | Empresa Exemplo (fictícia)</b><br/>Janeiro de 2024 - dezembro de 2025")
paragraph("Desenvolveu uma API REST em Laravel para gestão de pedidos. Utilizou MySQL para persistência e Git para controle de versão. Escreveu testes de integração com PHPUnit. Não declara experiência com Docker ou Kubernetes.")
paragraph("Formação", "CVSection")
paragraph("Tecnólogo em Análise e Desenvolvimento de Sistemas, concluído em 2023 na Instituição Exemplo (fictícia).")
paragraph("Competências e idiomas", "CVSection")
paragraph("PHP, Laravel, MySQL, Git, APIs REST e PHPUnit.<br/>Inglês intermediário: leitura de documentação técnica.")
paragraph("Projeto", "CVSection")
paragraph("Sistema de pedidos: desenvolvimento do backend em Laravel, consultas SQL e autenticação. Participou de revisões de código com a equipe.")
paragraph("Limites do exemplo", "CVSection")
paragraph("Este perfil não comprova cinco anos de Laravel, inglês fluente ou uso de Docker. Nenhuma informação pertence a uma pessoa real.")

def footer(canvas, doc):
    canvas.saveState()
    canvas.setFont("Helvetica", 9)
    canvas.setFillColor(colors.HexColor("#596574"))
    canvas.drawString(42, 28, "Point Carreiras | Currículo fictício para demonstração")
    canvas.drawRightString(A4[0] - 42, 28, str(doc.page))
    canvas.restoreState()

SimpleDocTemplate(str(output), pagesize=A4, rightMargin=42, leftMargin=42, topMargin=38, bottomMargin=42).build(story, onFirstPage=footer, onLaterPages=footer)
print(output)
