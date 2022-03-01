public class Main {
    public static void main(String args[]) throws Exception {
        Extractor extractor = new Extractor("/Users/CSCI572_HW4/FOX_News/URLtoHTML_fox_news.csv");
        extractor.extractAll("/Users/CSCI572_HW4/FOX_News/HTML_files");
    }
}
