import networkx as nx

G = nx.read_edgelist("/Users/CSCI572_HW4/FOX_News/edge_file.txt", create_using=nx.DiGraph())
pr = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight',dangling=None)
with open("external_pageRankFile.txt","w") as f:
        for node, value in pr.items():
            f.write(node + "=" + str(value) + "\n")

